<?php

namespace App\Controller;

use App\Repository\TopicRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function index(TopicRepository $topicRepository): Response
    {
        $topics = $topicRepository->findHomepageTopics();

        return $this->render('homepage.html.twig', [
            'topics' => $topics,
        ]);
    }

    #[Route('/topic/{slug}', name: 'app_topic_detail')]
    public function detail(string $slug, TopicRepository $topicRepository): Response
    {
        $topic = $topicRepository->findBySlug($slug);

        if (!$topic) {
            throw $this->createNotFoundException('Topic niet gevonden');
        }

        return $this->render('topic_detail.html.twig', [
            'topic' => $topic,
        ]);
    }

    #[Route('/topic/{slug}/toggle-homepage', name: 'app_topic_toggle_homepage', methods: ['POST'])]
    public function toggleHomepageVisibility(string $slug, Request $request, TopicRepository $topicRepository, EntityManagerInterface $entityManager): RedirectResponse
    {
        $topic = $topicRepository->findBySlug($slug);

        if (!$topic) {
            throw $this->createNotFoundException('Topic niet gevonden');
        }

        if (!$this->isCsrfTokenValid('toggle_homepage', $request->request->get('_token')))
        {
            throw $this->createAccessDeniedException('Ongeldig CSRF-token');
        }

        $topic->setShowOnHomepage(!$topic->isShowOnHomepage());
        $entityManager->persist($topic);
        $entityManager->flush();

        return $this->redirectToRoute('app_topic_detail', ['slug' => $topic->getSlug()]);
    }

    #[Route('/admin/topics', name: 'app_admin_topics', methods: ['GET', 'POST'])]
    public function adminTopics(Request $request, TopicRepository $topicRepository, EntityManagerInterface $entityManager): Response
    {
        $topics = $topicRepository->findAllOrderedByCreatedAt();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('admin_topics', $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Ongeldig CSRF-token');
            }

            $positions = $request->request->all('position');
            $visible = $request->request->all('showOnHomepage');

            foreach ($topics as $topic) {
                if ($topic->getId() === null) {
                    continue;
                }

                $topic->setPosition((int) ($positions[$topic->getId()] ?? 0));
                $topic->setShowOnHomepage(isset($visible[$topic->getId()]));
            }

            $entityManager->flush();
            $this->addFlash('success', 'Topics zijn bijgewerkt.');

            return $this->redirectToRoute('app_admin_topics');
        }

        return $this->render('admin_topics.html.twig', [
            'topics' => $topics,
        ]);
    }
}