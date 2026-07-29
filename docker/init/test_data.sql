-- Test data voor evounik_db
-- Gebruik: docker-compose exec mariadb mysql -u root -proot_password evounik_db < docker/init/test_data.sql

-- Topics
INSERT INTO topic (title, slug, image, summary, description, created_at, updated_at) VALUES
('Welkom bij Evounik', 'welkom-bij-evounik', '/images/welcome.jpg', 'Een introductie tot ons platform en wat je kunt verwachten.', 'Evounik is een modern platform dat zich richt op innovatie en samenwerking. Ontdek onze missie en visie voor de toekomst.', NOW(), NOW()),
('Duurzame Energie Oplossingen', 'duurzame-energie-oplossingen', '/images/energy.jpg', 'Ontdek hoe we bijdragen aan een groenere toekomst met duurzame energie.', 'We geloven in een duurzame toekomst. Onze energieoplossingen zijn ontworpen om de CO2-uitstoot te verminderen en efficiënter om te gaan met natuurlijke hulpbronnen.', NOW(), NOW()),
('Digital Transformatie', 'digitale-transformatie', '/images/digital.jpg', 'Hoe digitale transformatie bedrijven helpt groeien en innoveren.', 'Digitale transformatie is essentieel voor moderne bedrijven. Wij helpen organisaties bij het implementeren van cutting-edge technologieën.', NOW(), NOW());

-- Blocks voor Topic 1: Welkom bij Evounik
INSERT INTO block (topic_id, type, content, metadata, position, created_at, updated_at) VALUES
(1, 'text', 'Welkom bij Evounik! Wij zijn een dynamisch platform dat zich richt op het verbinden van mensen en technologie. Ons doel is om innovatieve oplossingen te bieden die een positieve impact hebben op de wereld.', NULL, 1, NOW(), NOW()),
(1, 'image', NULL, '{"url": "/images/team.jpg", "alt": "Evounik team", "caption": "Ons geweldige team"}', 2, NOW(), NOW()),
(1, 'text_image', 'Ons team bestaat uit gepassioneerde professionals met diverse achtergronden. Samen werken we aan projecten die er echt toe doen.', '{"url": "/images/office.jpg", "alt": "Kantoor", "side": "right"}', 3, NOW(), NOW()),
(1, 'diagram', NULL, '{"type": "pie", "data": {"Innovatie": 40, "Samenwerking": 30, "Duurzaamheid": 20, "Groei": 10}, "title": "Onze focusgebieden"}', 4, NOW(), NOW()),
(1, 'text', 'Sluit je aan bij onze community en maak deel uit van de verandering. Samen kunnen we meer bereiken!', NULL, 5, NOW(), NOW());

-- Blocks voor Topic 2: Duurzame Energie Oplossingen
INSERT INTO block (topic_id, type, content, metadata, position, created_at, updated_at) VALUES
(2, 'text', 'Duurzame energie is de toekomst. Bij Evounik zetten we ons in voor het ontwikkelen en implementeren van groene energieoplossingen die zowel economisch als ecologisch verantwoord zijn.', NULL, 1, NOW(), NOW()),
(2, 'image', NULL, '{"url": "/images/solar-panels.jpg", "alt": "Zonnepanelen", "caption": "Zonne-energie installatie"}', 2, NOW(), NOW()),
(2, 'text_image', 'Zonne-energie is een van de meest veelbelovende hernieuwbare energiebronnen. Onze zonnepaneelinstallaties helpen bedrijven en huishoudens om hun energiekosten te verlagen en hun ecologische voetafdruk te verkleinen.', '{"url": "/images/solar-diagram.jpg", "alt": "Zonne-energie diagram", "side": "left"}', 3, NOW(), NOW()),
(2, 'diagram', NULL, '{"type": "bar", "data": {"Zonne-energie": 45, "Windenergie": 30, "Waterkracht": 15, "Biomassa": 10}, "title": "Energiebronnen verdeling"}', 4, NOW(), NOW()),
(2, 'text', 'Onze windturbineprojecten leveren schone energie aan duizenden huishoudens. We werken samen met lokale gemeenschappen om windparken te ontwikkelen die in harmonie zijn met de omgeving.', NULL, 5, NOW(), NOW());

-- Blocks voor Topic 3: Digitale Transformatie
INSERT INTO block (topic_id, type, content, metadata, position, created_at, updated_at) VALUES
(3, 'text', 'Digitale transformatie is niet alleen een trend, het is een noodzaak. Bedrijven die zich aanpassen aan de digitale realiteit blijven concurrerend en relevant in de moderne markt.', NULL, 1, NOW(), NOW()),
(3, 'image', NULL, '{"url": "/images/digital-transformation.jpg", "alt": "Digitale transformatie", "caption": "De digitale reis"}', 2, NOW(), NOW()),
(3, 'text_image', 'Cloud computing vormt de basis van moderne digitale transformatie. Het stelt bedrijven in staat om flexibel te schalen, kosten te optimaliseren en innovatie te versnellen.', '{"url": "/images/cloud.jpg", "alt": "Cloud computing", "side": "right"}', 3, NOW(), NOW()),
(3, 'diagram', NULL, '{"type": "line", "data": {"2020": 20, "2021": 35, "2022": 50, "2023": 68, "2024": 82}, "title": "Digitale adoptie groei (%)"}', 4, NOW(), NOW()),
(3, 'text', 'AI en machine learning zijn de drijvende krachten achter de volgende golf van digitale transformatie. Ontdek hoe deze technologieën jouw bedrijf kunnen helpen groeien.', NULL, 5, NOW(), NOW()),
(3, 'text', 'Neem vandaag nog contact met ons op voor een gratis consultatie over hoe wij jouw organisatie kunnen helpen met digitale transformatie.', NULL, 6, NOW(), NOW());