<?php

/*
    public function testCreateMessageIllegalTemplateRef(): void
    {
        $service = new TestableAbstractEmailService(
            $this->authenticationService,
            $this->config,
            $this->mailTransport
        );

        $this->expectException(InvalidArgumentException::class);
        $service->createMessage('to@test.com', null);
    }

    public function testCreateMessageBadTemplate(): void
    {
        // Partial mock, so we can return a bad template from getTemplate()
        $service = new TestableAbstractEmailService(
            $this->authenticationService,
            $this->config,
            $this->mailTransport
        );

        $this->expectException(InvalidArgumentException::class);
        $this->localViewRenderer->shouldReceive('renderTemplate')
            ->with('feedback.twig', [])
            ->andReturn('bad html');

        $service->createMessage('to@test.com', 'email-feedback');
    }
*/
