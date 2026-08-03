<?php

namespace App\DataFixtures;

use App\Entity\Block;
use App\Entity\Topic;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // ---------------------------------------------------------------
        // Topic 1: Welkom bij Evounik
        // ---------------------------------------------------------------
        $topic1 = new Topic();
        $topic1->setTitle('Welkom bij Evounik');
        $topic1->setSlug('welkom-bij-evounik');
        $topic1->setImage('/images/welcome.jpg');
        $topic1->setSummary('Een introductie tot ons platform en wat je kunt verwachten.');
        $topic1->setDescription('Evounik is een modern platform dat zich richt op innovatie en samenwerking. Ontdek onze missie en visie voor de toekomst.');
        $topic1->setPosition(1);
        $topic1->setShowOnHomepage(true);

        $block1_1 = new Block();
        $block1_1->setType('text');
        $block1_1->setContent('Welkom bij Evounik! Wij zijn een dynamisch platform dat zich richt op het verbinden van mensen en technologie. Ons doel is om innovatieve oplossingen te bieden die een positieve impact hebben op de wereld.');
        $block1_1->setPosition(1);
        $topic1->addBlock($block1_1);

        $block1_2 = new Block();
        $block1_2->setType('image');
        $block1_2->setMetadata(['url' => '/images/team.jpg', 'alt' => 'Evounik team', 'caption' => 'Ons geweldige team']);
        $block1_2->setPosition(2);
        $topic1->addBlock($block1_2);

        $block1_3 = new Block();
        $block1_3->setType('text_image');
        $block1_3->setContent('Ons team bestaat uit gepassioneerde professionals met diverse achtergronden. Samen werken we aan projecten die er echt toe doen.');
        $block1_3->setMetadata(['url' => '/images/office.jpg', 'alt' => 'Kantoor', 'side' => 'right']);
        $block1_3->setPosition(3);
        $topic1->addBlock($block1_3);

        $block1_4 = new Block();
        $block1_4->setType('diagram');
        $block1_4->setMetadata([
            'type' => 'pie',
            'data' => ['Innovatie' => 40, 'Samenwerking' => 30, 'Duurzaamheid' => 20, 'Groei' => 10],
            'title' => 'Onze focusgebieden',
        ]);
        $block1_4->setPosition(4);
        $topic1->addBlock($block1_4);

        $block1_5 = new Block();
        $block1_5->setType('text');
        $block1_5->setContent('Sluit je aan bij onze community en maak deel uit van de verandering. Samen kunnen we meer bereiken!');
        $block1_5->setPosition(5);
        $topic1->addBlock($block1_5);

        $manager->persist($topic1);

        // ---------------------------------------------------------------
        // Topic 2: Duurzame Energie Oplossingen
        // ---------------------------------------------------------------
        $topic2 = new Topic();
        $topic2->setTitle('Duurzame Energie Oplossingen');
        $topic2->setSlug('duurzame-energie-oplossingen');
        $topic2->setImage('/images/energy.jpg');
        $topic2->setSummary('Ontdek hoe we bijdragen aan een groenere toekomst met duurzame energie.');
        $topic2->setDescription('We geloven in een duurzame toekomst. Onze energieoplossingen zijn ontworpen om de CO2-uitstoot te verminderen en efficiënter om te gaan met natuurlijke hulpbronnen.');
        $topic2->setPosition(2);
        $topic2->setShowOnHomepage(true);

        $block2_1 = new Block();
        $block2_1->setType('text');
        $block2_1->setContent('Duurzame energie is de toekomst. Bij Evounik zetten we ons in voor het ontwikkelen en implementeren van groene energieoplossingen die zowel economisch als ecologisch verantwoord zijn.');
        $block2_1->setPosition(1);
        $topic2->addBlock($block2_1);

        $block2_2 = new Block();
        $block2_2->setType('image');
        $block2_2->setMetadata(['url' => '/images/solar-panels.jpg', 'alt' => 'Zonnepanelen', 'caption' => 'Zonne-energie installatie']);
        $block2_2->setPosition(2);
        $topic2->addBlock($block2_2);

        $block2_3 = new Block();
        $block2_3->setType('text_image');
        $block2_3->setContent('Zonne-energie is een van de meest veelbelovende hernieuwbare energiebronnen. Onze zonnepaneelinstallaties helpen bedrijven en huishoudens om hun energiekosten te verlagen en hun ecologische voetafdruk te verkleinen.');
        $block2_3->setMetadata(['url' => '/images/solar-diagram.jpg', 'alt' => 'Zonne-energie diagram', 'side' => 'left']);
        $block2_3->setPosition(3);
        $topic2->addBlock($block2_3);

        $block2_4 = new Block();
        $block2_4->setType('diagram');
        $block2_4->setMetadata([
            'type' => 'bar',
            'data' => ['Zonne-energie' => 45, 'Windenergie' => 30, 'Waterkracht' => 15, 'Biomassa' => 10],
            'title' => 'Energiebronnen verdeling',
        ]);
        $block2_4->setPosition(4);
        $topic2->addBlock($block2_4);

        $block2_5 = new Block();
        $block2_5->setType('text');
        $block2_5->setContent('Onze windturbineprojecten leveren schone energie aan duizenden huishoudens. We werken samen met lokale gemeenschappen om windparken te ontwikkelen die in harmonie zijn met de omgeving.');
        $block2_5->setPosition(5);
        $topic2->addBlock($block2_5);

        $manager->persist($topic2);

        // ---------------------------------------------------------------
        // Topic 3: Digital Transformatie
        // ---------------------------------------------------------------
        $topic3 = new Topic();
        $topic3->setTitle('Digital Transformatie');
        $topic3->setSlug('digitale-transformatie');
        $topic3->setImage('/images/digital.jpg');
        $topic3->setSummary('Hoe digitale transformatie bedrijven helpt groeien en innoveren.');
        $topic3->setDescription('Digitale transformatie is essentieel voor moderne bedrijven. Wij helpen organisaties bij het implementeren van cutting-edge technologieën.');
        $topic3->setPosition(3);
        $topic3->setShowOnHomepage(true);

        $block3_1 = new Block();
        $block3_1->setType('text');
        $block3_1->setContent('Digitale transformatie is niet alleen een trend, het is een noodzaak. Bedrijven die zich aanpassen aan de digitale realiteit blijven concurrerend en relevant in de moderne markt.');
        $block3_1->setPosition(1);
        $topic3->addBlock($block3_1);

        $block3_2 = new Block();
        $block3_2->setType('image');
        $block3_2->setMetadata(['url' => '/images/digital-transformation.jpg', 'alt' => 'Digitale transformatie', 'caption' => 'De digitale reis']);
        $block3_2->setPosition(2);
        $topic3->addBlock($block3_2);

        $block3_3 = new Block();
        $block3_3->setType('text_image');
        $block3_3->setContent('Cloud computing vormt de basis van moderne digitale transformatie. Het stelt bedrijven in staat om flexibel te schalen, kosten te optimaliseren en innovatie te versnellen.');
        $block3_3->setMetadata(['url' => '/images/cloud.jpg', 'alt' => 'Cloud computing', 'side' => 'right']);
        $block3_3->setPosition(3);
        $topic3->addBlock($block3_3);

        $block3_4 = new Block();
        $block3_4->setType('diagram');
        $block3_4->setMetadata([
            'type' => 'line',
            'data' => ['2020' => 20, '2021' => 35, '2022' => 50, '2023' => 68, '2024' => 82],
            'title' => 'Digitale adoptie groei (%)',
        ]);
        $block3_4->setPosition(4);
        $topic3->addBlock($block3_4);

        $block3_5 = new Block();
        $block3_5->setType('text');
        $block3_5->setContent('AI en machine learning zijn de drijvende krachten achter de volgende golf van digitale transformatie. Ontdek hoe deze technologieën jouw bedrijf kunnen helpen groeien.');
        $block3_5->setPosition(5);
        $topic3->addBlock($block3_5);

        $block3_6 = new Block();
        $block3_6->setType('text');
        $block3_6->setContent('Neem vandaag nog contact met ons op voor een gratis consultatie over hoe wij jouw organisatie kunnen helpen met digitale transformatie.');
        $block3_6->setPosition(6);
        $topic3->addBlock($block3_6);

        $manager->persist($topic3);

        $manager->flush();
    }
}
