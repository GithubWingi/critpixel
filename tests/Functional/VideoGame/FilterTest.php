<?php

declare(strict_types=1);

namespace App\Tests\Functional\VideoGame;

use App\Tests\Functional\FunctionalTestCase;

final class FilterTest extends FunctionalTestCase
{
    public function testShouldListTenVideoGames(): void
    {
        $this->get('/');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');
        $this->client->clickLink('2');
        self::assertResponseIsSuccessful();
    }

    public function testShouldFilterVideoGamesBySearch(): void
    {
        $this->get('/');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');
        $this->client->submitForm('Filtrer', ['filter[search]' => 'Jeu vidéo 49'], 'GET');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, 'article.game-card');
    }

    /**
     * @dataProvider tagProvider
     *
     * @param array<int|string> $tags
     */
    public function testShouldFilterByTagsVideoGames(
        array $tags,
        int $expectedCount,
        ?string $expectedException = null,
    ): void {
        // Accès à la page d'accueil
        $crawler = $this->get('/');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');

        // Formulaire
        $form = $crawler->selectButton('Filtrer')->form();

        if ($expectedException) {
            $this->expectException($expectedException);
        }

        // Simuler le click sur un tag
        $form['filter[tags]'] = $tags;

        // Soumission du formulaire
        $this->client->submit($form);

        self::assertResponseIsSuccessful();
        self::assertSelectorCount($expectedCount, 'article.game-card');
    }

    /**
     * @return array<string, array{0: array<int>, 1: int, 2?: class-string<\InvalidArgumentException>}>
     */
    public static function tagProvider(): array
    {
        return [
            'no tags' => [
                [], // aucun tag
                10, // nombre de jeux attendu
            ],
            'one tag' => [
                [1], // un tag
                10,   // nombre de jeux attendu pour ce tag
            ],
            'multiple tags' => [
                [1, 2, 3, 4], // plusieurs tags
                4,            // nombre de jeux attendu
            ],
            'non-existent tag' => [
                [999], // id qui n’existe pas dans le formulaire
                0, // pas utilisé ici car exception attendue
                \InvalidArgumentException::class,
            ],
        ];
    }
}
