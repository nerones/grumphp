<?php

namespace spec\GrumPHP\Event\Subscriber;

use GrumPHP\Collection\TasksCollection;
use GrumPHP\Event\RunnerEvent;
use GrumPHP\Event\Subscriber\ProgressSubscriber;
use GrumPHP\Event\TaskEvent;
use GrumPHP\Task\Config\Metadata;
use GrumPHP\Task\Config\TaskConfig;
use GrumPHP\Task\TaskInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProgressSubscriberSpec extends ObjectBehavior
{
    function let(OutputInterface $output)
    {
        $output->getVerbosity()->willReturn(OutputInterface::VERBOSITY_NORMAL);
        $output->isDecorated()->willReturn(false);
        $output->getFormatter()->willReturn(new OutputFormatter());

        $this->beConstructedWith($output);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(ProgressSubscriber::class);
    }

    function it_is_an_event_subscriber()
    {
        $this->shouldImplement(EventSubscriberInterface::class);
    }

    function it_should_subscribe_to_events()
    {
        $this->getSubscribedEvents()->shouldBeArray();
    }

    function it_starts_progress(OutputInterface $output, RunnerEvent $event, TasksCollection $tasks)
    {
        $tasks->count()->willReturn(2);
        $event->getTasks()->willReturn($tasks);

        $output->write('<fg=yellow>GrumPHP is sniffing your code!</fg=yellow>')->shouldBeCalled();

        $this->startProgress($event);
    }

    function it_should_advance_progress(OutputInterface $output, TaskEvent $event, TaskInterface $task)
    {
        $event->getTask()->willReturn($task);
        $task->getConfig()->willReturn(new TaskConfig('task1', [], new Metadata([])));

        $output->writeln('')->willReturn(null);
        $output->write(Argument::containingString('Running task'))->shouldBeCalled();
        $output->write(Argument::containingString('1/2'))->shouldBeCalled();

        $this->createProgressBar(2);
        $this->advanceProgress($event);
    }

    function it_finishes_progress(OutputInterface $output, RunnerEvent $event)
    {
        $output->writeln('')->shouldBeCalled();

        $this->createProgressBar(0);
        $this->finishProgress($event);
    }

    function it_finishes_progress_early(OutputInterface $output, RunnerEvent $event)
    {
        $output->write(Argument::containingString('<fg=red>Aborted ...</fg=red>'))->shouldBeCalled();
        $output->writeln('')->shouldBeCalled();

        $this->createProgressBar(2);
        $this->finishProgress($event);
    }
}
