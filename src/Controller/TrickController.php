<?php

namespace App\Controller;

use App\Entity\Comment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\CommentType;
use App\Repository\TrickRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class TrickController extends AbstractController
{
    
    #[Route('/tricks/{slug}', name: 'app_trick')]
    public function index($slug, TrickRepository $repository, Request $request, EntityManagerInterface $manager): Response
    {
        $unique = $repository->findOneBy(['slug' => $slug]);
        $user = $this->getUser();

        $form = $this->createForm(CommentType::class);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $data = $form->getData();

            $comment = new Comment;
            $comment->setContent($data->getContent());
            $comment->setTrick($unique);
            $comment->setCreatedAt(new \DateTimeImmutable('now'));
            $comment->setUser($user);
            $manager->persist($comment);
            $manager->flush();

            return $this->redirectToRoute('app_trick', ['slug' => $unique->getSlug()]);
        }

        return $this->render('trick/index.html.twig', [
            'form' => $form->createView(),
            'trick' => $unique,
        ]);
    }





    #[Route('/get-more-tricks/{offset}/{limit}', name: 'get_more_tricks', methods: ['GET'])]
    public function getMoreTricks(TrickRepository $repository, $offset, $limit): JsonResponse
    {
        $all = $repository->findAll();

        $tricksData = [];
        foreach ($all as $trick) {
            $firstImage = null;
            $images = $trick->getImages();
            if (!empty($images)) {
                $firstImage = $images[0]->getPath();
            } else {
                $firstImage = "/assets/img/default.jpg";
            }

            $tricksData[] = [
                'name' => $trick->getName(),
                'slug' => $trick->getSlug(),
                'image' => $firstImage,
            ];
        }

        $moreTricks = array_slice($tricksData, $offset, $limit);

        return new JsonResponse($moreTricks);
    }
}
