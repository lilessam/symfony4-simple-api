<?php

namespace App\Command;

use App\Entity\Post;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CheckStatus extends Command
{
    protected static $defaultName = 'app:check-status';
    //
    private $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->container = $container;
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this->addArgument('post_id', InputArgument::REQUIRED, 'Post ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $id = $input->getArgument('post_id') ?: null;

        if (!$id) {
            return false;
        }
        //
        $repository = $this->container->get('doctrine')->getRepository(Post::class);
        $post = $repository->find($id);

        sleep(60 * 5);
        if ($post->getStatus() == Post::STATUS_PENDING) {

            $post->setStatus(Post::STATUS_REJECTED);
            $em = $this->container->get('doctrine')->getManager();
            $em->persist($post);
            $em->flush();
        }
    }
}
