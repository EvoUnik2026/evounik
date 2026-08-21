<?php

namespace App\Controller;

use App\Entity\Block;
use App\Entity\Topic;
use App\Repository\BlockRepository;
use App\Repository\TopicRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\AsciiSlugger;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;

/**
 * Admin management board for topics and their blocks.
 *
 * All routes live under /admin and are guarded by ROLE_ADMIN (both via the
 * access_control in security.yaml and an explicit denyAccessUnlessGranted()),
 * so only administrators can manage content.
 *
 * Forms are handled manually (no Symfony Form component) to stay lightweight
 * and consistent with the existing DefaultController::adminTopics() pattern.
 */
class AdminTopicController extends AbstractController
{
    private const BLOCK_TYPES = ['text', 'image', 'text_image', 'diagram'];

    /**
     * Edit a topic's fields and manage its blocks.
     */
    public function editTopic(int $id, Request $request, TopicRepository $topics, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $topic = $topics->find($id);
        if (!$topic) {
            throw $this->createNotFoundException('Topic niet gevonden');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('topic_edit', $request->request->get('_token'))) {
                $this->addFlash('error', 'Ongeldig CSRF-token.');
            } else {
                $topic->setTitle((string) $request->request->get('title', ''));
                $topic->setSlug((string) $request->request->get('slug', '') ?: $this->slugify($topic->getTitle()));
                $topic->setImage($this->nullableString($request->request->get('image')));
                $topic->setSummary($this->nullableString($request->request->get('summary')));
                $topic->setDescription($this->nullableString($request->request->get('description')));
                $topic->setPosition((int) $request->request->get('position', 0));
                $topic->setShowOnHomepage((bool) $request->request->get('showOnHomepage'));

                $existing = $topics->findBy(['slug' => $topic->getSlug()]);
                $duplicate = array_values(array_filter($existing, fn (Topic $t): bool => $t->getId() !== $topic->getId()));
                if ($duplicate) {
                    $this->addFlash('error', 'Er bestaat al een topic met deze slug.');
                } else {
                    $topic->setUpdatedAt(new \DateTimeImmutable());
                    $em->persist($topic);
                    $em->flush();
                    $this->addFlash('success', 'Topic is opgeslagen.');
                }
            }

            return $this->redirectToRoute('app_admin_topic_edit', ['id' => $topic->getId()]);
        }

        return $this->render('admin/topic_edit.html.twig', [
            'topic' => $topic,
            'blockTypes' => self::BLOCK_TYPES,
        ]);
    }

    /**
     * Create a new topic and jump straight into editing it.
     */
    public function newTopic(Request $request, TopicRepository $topics, EntityManagerInterface $em): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('topic_new', $request->request->get('_token'))) {
            $this->addFlash('error', 'Ongeldig CSRF-token.');

            return $this->redirectToRoute('app_admin_topics');
        }

        $title = trim((string) $request->request->get('title', ''));
        if ('' === $title) {
            $this->addFlash('error', 'Titel mag niet leeg zijn.');

            return $this->redirectToRoute('app_admin_topics');
        }

        $baseSlug = $this->slugify($title);
        $slug = $baseSlug;
        $counter = 2;
        while ($topics->findOneBy(['slug' => $slug])) {
            $slug = $baseSlug.'-'.$counter++;
        }

        $topic = new Topic();
        $topic->setTitle($title);
        $topic->setSlug($slug);
        $topic->setPosition(0);
        $topic->setShowOnHomepage(false);
        $topic->setCreatedAt(new \DateTimeImmutable());
        $topic->setUpdatedAt(new \DateTimeImmutable());

        $em->persist($topic);
        $em->flush();

        $this->addFlash('success', sprintf('Topic "%s" is aangemaakt.', $title));

        return $this->redirectToRoute('app_admin_topic_edit', ['id' => $topic->getId()]);
    }

    /**
     * Create a new block for a topic and jump straight into editing it.
     */
    public function newBlock(int $id, Request $request, TopicRepository $topics, EntityManagerInterface $em): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $topic = $topics->find($id);
        if (!$topic) {
            throw $this->createNotFoundException('Topic niet gevonden');
        }

        if (!$this->isCsrfTokenValid('block_new', $request->request->get('_token'))) {
            $this->addFlash('error', 'Ongeldig CSRF-token.');

            return $this->redirectToRoute('app_admin_topic_edit', ['id' => $topic->getId()]);
        }

        $type = (string) $request->request->get('type', 'text');
        if (!in_array($type, self::BLOCK_TYPES, true)) {
            $type = 'text';
        }

        $block = new Block();
        $block->setType($type);
        $block->setContent('');
        $block->setMetadata($this->defaultMetadataForType($type));
        $block->setTopic($topic);
        $block->setPosition(count($topic->getBlocks()) + 1);
        $block->setCreatedAt(new \DateTimeImmutable());
        $block->setUpdatedAt(new \DateTimeImmutable());

        $em->persist($block);
        $em->flush();

        $this->addFlash('success', sprintf('Blok "%s" is toegevoegd.', ucfirst($type)));

        return $this->redirectToRoute('app_admin_block_edit', ['id' => $block->getId()]);
    }

    /**
     * Edit a single block. Fields depend on the block type; text and
     * text_image blocks use the WYSIWYG editor.
     */
    public function editBlock(int $id, Request $request, BlockRepository $blocks, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $block = $blocks->find($id);
        if (!$block) {
            throw $this->createNotFoundException('Blok niet gevonden');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('block_edit', $request->request->get('_token'))) {
                $this->addFlash('error', 'Ongeldig CSRF-token.');
            } else {
                $this->applyBlockForm($block, $request);
                $block->setUpdatedAt(new \DateTimeImmutable());
                $em->persist($block);
                $em->flush();
                $this->addFlash('success', 'Blok is opgeslagen.');
            }

            return $this->redirectToRoute('app_admin_block_edit', ['id' => $block->getId()]);
        }

        return $this->render('admin/block_edit.html.twig', [
            'block' => $block,
            'topic' => $block->getTopic(),
            'blockTypes' => self::BLOCK_TYPES,
            'diagramData' => $this->diagramDataToText($block->getMetadata()['data'] ?? null),
        ]);
    }

    /**
     * Delete a block.
     */
    public function deleteBlock(int $id, Request $request, BlockRepository $blocks, EntityManagerInterface $em): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $block = $blocks->find($id);
        if (!$block) {
            throw $this->createNotFoundException('Blok niet gevonden');
        }

        $topicId = $block->getTopic()?->getId();

        if (!$this->isCsrfTokenValid('block_delete', $request->request->get('_token'))) {
            $this->addFlash('error', 'Ongeldig CSRF-token.');
        } else {
            $em->remove($block);
            $em->flush();
            $this->addFlash('success', 'Blok is verwijderd.');
            $this->renumberBlocks($topicId, $em);
        }

        return $this->redirectToRoute('app_admin_topic_edit', ['id' => (int) $topicId]);
    }

    /**
     * Move a block up or down within its topic.
     */
    public function moveBlock(int $id, Request $request, BlockRepository $blocks, EntityManagerInterface $em): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $block = $blocks->find($id);
        if (!$block || null === $block->getTopic()) {
            throw $this->createNotFoundException('Blok niet gevonden');
        }

        $topicId = $block->getTopic()->getId();

        if (!$this->isCsrfTokenValid('block_move_'.$block->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Ongeldig CSRF-token.');
        } else {
            $direction = $request->request->get('direction', 'down');
            $siblings = $blocks->findByTopic($topicId);
            $currentIndex = null;

            foreach ($siblings as $i => $sibling) {
                if ($sibling->getId() === $block->getId()) {
                    $currentIndex = $i;
                    break;
                }
            }

            if (null !== $currentIndex) {
                $swapIndex = 'up' === $direction ? $currentIndex - 1 : $currentIndex + 1;

                if (isset($siblings[$swapIndex])) {
                    $swap = $siblings[$swapIndex];
                    $tmp = $block->getPosition();
                    $block->setPosition($swap->getPosition());
                    $swap->setPosition($tmp);
                    $em->persist($block);
                    $em->persist($swap);
                    $em->flush();
                }
            }

            $this->renumberBlocks($topicId, $em);
        }

        return $this->redirectToRoute('app_admin_topic_edit', ['id' => (int) $topicId]);
    }

    private function applyBlockForm(Block $block, Request $request): void
    {
        $type = $block->getType();

        if ('text' === $type || 'text_image' === $type) {
            $block->setContent((string) $request->request->get('content', ''));
        }

        $meta = $block->getMetadata() ?? [];
        $meta['url'] = $this->nullableString($request->request->get('url'));
        $meta['alt'] = $this->nullableString($request->request->get('alt'));
        $meta['caption'] = $this->nullableString($request->request->get('caption'));

        if ('text_image' === $type) {
            $side = (string) $request->request->get('side', 'right');
            $meta['side'] = in_array($side, ['left', 'right'], true) ? $side : 'right';
        }

        if ('diagram' === $type) {
            $chartType = (string) $request->request->get('diagram_type', 'pie');
            $meta['type'] = in_array($chartType, ['pie', 'bar', 'line'], true) ? $chartType : 'pie';
            $meta['title'] = $this->nullableString($request->request->get('title'));
            $meta['data'] = $this->parseDiagramData((string) $request->request->get('data_raw'));
        }

        $block->setMetadata($meta);
    }

    private function defaultMetadataForType(string $type): array
    {
        return match ($type) {
            'image' => ['url' => '', 'alt' => '', 'caption' => ''],
            'text_image' => ['url' => '', 'alt' => '', 'side' => 'right'],
            'diagram' => ['type' => 'pie', 'title' => '', 'data' => []],
            default => [],
        };
    }

    /**
     * Convert lines of "Label: value" (or "Label = value") into a data map.
     */
    private function parseDiagramData(string $raw): array
    {
        $data = [];

        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $line = trim($line);
            if ('' === $line) {
                continue;
            }

            if (preg_match('/^\s*(.+?)\s*[:=]\s*(.+?)\s*$/', $line, $m)) {
                $data[$m[1]] = (float) $m[2];
            }
        }

        return $data;
    }

    private function diagramDataToText(?array $data): string
    {
        if (!$data) {
            return '';
        }

        $lines = [];
        foreach ($data as $label => $value) {
            $lines[] = $label.': '.$value;
        }

        return implode("\n", $lines);
    }

    /**
     * Renumber block positions 1..n for a topic after a delete/reorder.
     */
    private function renumberBlocks(?int $topicId, EntityManagerInterface $em): void
    {
        if (!$topicId) {
            return;
        }

        $position = 1;
        foreach ($this->getBlockRepository($em)->findByTopic($topicId) as $block) {
            if ($block->getPosition() !== $position) {
                $block->setPosition($position);
                $em->persist($block);
            }
            ++$position;
        }
        $em->flush();
    }

    private function getBlockRepository(EntityManagerInterface $em): BlockRepository
    {
        return $em->getRepository(Block::class);
    }

    private function slugify(string $text): string
    {
        return (string) (new AsciiSlugger())->slug($text)->lower();
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return '' === $value ? null : $value;
    }
}
