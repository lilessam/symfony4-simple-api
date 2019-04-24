<?php

namespace App\Controller;

use App\Entity\Post;
use App\Traits\Mails;
use App\Form\PostType;
use App\Processor\CheckStatusProcessor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Messenger\MessageBusInterface;
use FOS\OAuthServerBundle\Model\ClientManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Movie controller.
 * @Route("/api", name="api_")
 */
class PostController extends FOSRestController
{
    use Mails;

    /**
     * Lists all Posts.
     * @Rest\Get("/posts")
     *
     * @return Response
     */
    public function list()
    {
        $repository = $this->getDoctrine()->getRepository(Post::class);
        $posts = $repository->findall();

        return $this->handleView($this->view($posts));
    }

    /**
     * Create Post.
     * @Rest\Post("/posts")
     *
     * @return Response
     */
    public function store(Request $request, MessageBusInterface $bus)
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);

        $data = json_decode($request->getContent(), true);
        $data = array_merge($data, ['status' => Post::STATUS_PENDING]);

        $form->submit($data);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($post);
            $em->flush();

            $bus->dispatch(new CheckStatusProcessor($post->getId()));

            return $this->handleView($this->view(['status' => 'ok'], Response::HTTP_CREATED));
        }

        return $this->handleView($this->view($form->getErrors()));
    }

    /**
     * Update Post.
     * @Rest\Post("/posts/{id}")
     *
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $repository = $this->getDoctrine()->getRepository(Post::class);
        $post = $repository->find($id);
        $form = $this->createForm(PostType::class, $post);

        $data = json_decode($request->getContent(), true);
        $data = array_merge($data, ['status' => $post->getStatus()]);

        $form->submit($data);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($post);
            $em->flush();

            return $this->handleView($this->view(['status' => 'ok'], Response::HTTP_CREATED));
        }

        return $this->handleView($this->view($form->getErrors()));
    }

    /**
     * Approve Post.
     * @Rest\Post("/posts_approve/{id}")
     *
     * @return Response
     */
    public function approve(Request $request, $id, \Swift_Mailer $mailer)
    {
        $repository = $this->getDoctrine()->getRepository(Post::class);
        $post = $repository->find($id);
        $post->setStatus(Post::STATUS_APPROVED);
        $em = $this->getDoctrine()->getManager();
        $em->persist($post);
        $em->flush();

        $this->sendApprovementMail($mailer, $post);

        return $this->handleView($this->view(['status' => 'ok'], Response::HTTP_CREATED));
    }

    /**
     * Reject Post.
     * @Rest\Post("/posts_reject/{id}")
     *
     * @return Response
     */
    public function reject(Request $request, $id, \Swift_Mailer $mailer, ClientManagerInterface $client_manager)
    {
        $repository = $this->getDoctrine()->getRepository(Post::class);
        $post = $repository->find($id);
        $post->setStatus(Post::STATUS_REJECTED);
        $em = $this->getDoctrine()->getManager();
        $em->persist($post);
        $em->flush();

        $this->sendRejectionMail($mailer, $post);

        return $this->handleView($this->view(['status' => 'ok'], Response::HTTP_CREATED));
    }
}
