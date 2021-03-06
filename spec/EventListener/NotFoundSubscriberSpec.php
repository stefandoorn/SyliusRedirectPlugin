<?php

declare(strict_types=1);

namespace spec\Setono\SyliusRedirectPlugin\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Setono\SyliusRedirectPlugin\EventListener\NotFoundSubscriber;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Model\RedirectionPath;
use Setono\SyliusRedirectPlugin\Resolver\RedirectionPathResolverInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class NotFoundSubscriberSpec extends ObjectBehavior
{
    public function let(
        ObjectManager $objectManager,
        ChannelContextInterface $channelContext,
        RedirectionPathResolverInterface $redirectionPathResolver,
        ChannelInterface $channel
    ): void {
        $channelContext->getChannel()->willReturn($channel);
        $redirectionPathResolver
            ->resolveFromRequest(Argument::type(Request::class), Argument::type(ChannelInterface::class), true)
            ->willReturn(new RedirectionPath())
        ;

        $this->beConstructedWith($objectManager, $channelContext, $redirectionPathResolver);
    }

    public function it_is_a_not_found_listener(): void
    {
        $this->shouldHaveType(NotFoundSubscriber::class);
    }

    public function it_implements_event_subscriber_interface(): void
    {
        $this->shouldImplement(EventSubscriberInterface::class);
    }

    public function it_does_not_redirect_request_that_are_not_master_request(
        ExceptionEvent $event
    ): void {
        $event->getRequestType()->willReturn(HttpKernelInterface::SUB_REQUEST);

        $event->getException()->shouldNotBeCalled();

        $event->setResponse(Argument::any())->shouldNotBeCalled();

        $this->onKernelException($event);
    }

    public function it_does_not_redirect_successful_events(
        ExceptionEvent $event,
        HttpException $exception
    ): void {
        $event->getRequestType()->willReturn(HttpKernelInterface::MASTER_REQUEST);

        $event->getException()->willReturn($exception);
        $exception->getStatusCode()->willReturn(500);

        $event->setResponse(Argument::any())->shouldNotBeCalled();

        $this->onKernelException($event);
    }

    public function it_does_not_redirect_if_there_is_no_redirect_defined(
        ExceptionEvent $event,
        HttpException $exception,
        Request $request
    ): void {
        $event->getRequestType()->willReturn(HttpKernelInterface::MASTER_REQUEST);

        $event->getException()->willReturn($exception);
        $exception->getStatusCode()->willReturn(Response::HTTP_NOT_FOUND);

        $event->getRequest()->willReturn($request);
        $request->getPathInfo()->willReturn('/404');

        $event->setResponse(Argument::any())->shouldNotBeCalled();

        $this->onKernelException($event);
    }

    public function it_redirects_if_there_is_a_redirect(
        ExceptionEvent $event,
        HttpException $exception,
        Request $request,
        ObjectManager $objectManager,
        RedirectInterface $redirect,
        RedirectionPathResolverInterface $redirectionPathResolver
    ): void {
        $event->getRequestType()->willReturn(HttpKernelInterface::MASTER_REQUEST);

        $event->getException()->willReturn($exception);
        $exception->getStatusCode()->willReturn(Response::HTTP_NOT_FOUND);

        $event->getRequest()->willReturn($request);
        $request->getPathInfo()->willReturn('/404');

        $redirectionPath = new RedirectionPath();
        $redirectionPath->addRedirect($redirect->getWrappedObject());

        $redirectionPathResolver
            ->resolveFromRequest(Argument::type(Request::class), Argument::type(ChannelInterface::class), true)
            ->willReturn($redirectionPath)
        ;

        $redirect->onAccess()->shouldBeCalled();
        $redirect->getDestination()->willReturn('/404-de');
        $redirect->isPermanent()->willReturn(true);

        $objectManager->flush()->shouldBeCalled();

        $event->setResponse(new RedirectResponse('/404-de', 301))->shouldBeCalled();

        $this->onKernelException($event);
    }
}
