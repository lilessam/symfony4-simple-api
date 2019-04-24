<?php

namespace App\Traits;

trait Mails
{
    public function sendMail($mailer, $post, $type)
    {
        $message = (new \Swift_Message('Post ' . ucfirst($type) . ': ' . $post->getTitle()))
        ->setFrom($post->getEmail())
        ->setTo($post->getEmail())
        ->setBody(
            $this->renderView(
                'emails/post_' . $type . '.html.twig',
                ['title' => $post->getTitle()]
            ),
            'text/html'
        )
        ->addPart(
            $this->renderView(
                'emails/post_' . $type . '.txt.twig',
                ['title' => $post->getTitle()]
            ),
            'text/plain'
        );
        $mailer->send($message);
    }

    /**
     * @return void
     */
    public function sendRejectionMail($mailer, $post)
    {
        // Send rejection email
        $this->sendMail($mailer, $post, 'rejected');
    }

    /**
     * @return void
     */
    public function sendApprovementMail($mailer, $post)
    {
        // Send approvement email
        $this->sendMail($mailer, $post, 'approved');
    }
}
