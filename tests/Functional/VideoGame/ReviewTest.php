<?php

declare(strict_types=1);

namespace App\Tests\Functional\VideoGame;

use App\Model\Entity\Review;
use App\Model\Entity\VideoGame;
use App\Tests\Functional\FunctionalTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ReviewTest extends FunctionalTestCase
{
    public function testShouldPostReview(): void
    {
        // Connexion de l'utilisateur
        $this->login();

        $expectedRating = '3';
        $expectedComment = 'Un bon petit jeu';

        // Accès à la page du jeu vidéo
        $crawler = $this->get('/jeu-video-0');
        self::assertResponseIsSuccessful();

        // Remplissage du formulaire
        $form = $crawler->selectButton('Poster')->form([
            'review[rating]' => $expectedRating,
            'review[comment]' => $expectedComment,
        ]);

        // Envoi du formulaire
        $this->client->submit($form);

        // Vérifie redirection après soumission
        self::assertResponseStatusCodeSame(Response::HTTP_FOUND);
        $crawler = $this->client->followRedirect();

        // Le formulaire ne doit plus s’afficher (déjà noté)
        self::assertSelectorNotExists("form[name='review']");

        // Vérifie que les données sont visibles sur la page
        self::assertSelectorTextContains('div.list-group-item:last-child h3', 'user+0');
        self::assertSelectorTextContains('div.list-group-item:last-child p', $expectedComment);
        self::assertSelectorTextContains('div.list-group-item:last-child span.value', $expectedRating);

        // Vérifie que la review est bien présente en base de données
        $user = $this->getUser();
        $videoGame = $this->getEntityManager()->getRepository(VideoGame::class)->findOneBy([
            'slug' => 'jeu-video-0',
        ]);
        self::assertNotNull($videoGame);

        $review = $this->getEntityManager()->getRepository(Review::class)->findOneBy([
            'videoGame' => $videoGame,
            'user' => $user,
        ]);

        self::assertNotNull($review);
        self::assertEquals($expectedRating, $review->getRating());
        self::assertEquals($expectedComment, $review->getComment());
    }

    /**
     * @dataProvider provideInvalidReviews
     */
    public function testShouldNotAllowInvalidReview(?string $rating, ?string $comment): void
    {
        // Connecte l"utilisateur
        $this->login();

        // Accès à la page du jeu vidéo
        $crawler = $this->get('/jeu-video-1');
        self::assertResponseIsSuccessful();

        // Remplit les champs du formulaire uniquement si les données sont fournies
        $form = $crawler->selectButton('Poster')->form();
        $form['review[rating]'] = $rating ?? '';
        if (null !== $comment) {
            $form['review[comment]'] = $comment;
        }

        // Soumet le formulaire avec les données invalides
        $this->client->submit($form);
        // Vérifie que la réponse est une erreur de validation (HTTP 422)
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Vérifie que le formulaire est inaccessible pour un utilisateur non connecté.
     *
     * @dataProvider provideUnauthenticatedAccessScenarios
     */
    public function testShouldRestrictAccessForUnauthenticatedUser(
        string $method,
        string $uri,
        int $expectedStatusCode,
        ?callable $assertion = null,
    ): void {
        if ('GET' === $method) {
            $this->get($uri);
        } elseif ('POST' === $method) {
            $this->post($uri, [
                'review' => [
                    'rating' => '3',
                    'comment' => 'Tentative non autorisée',
                ],
            ]);
        }

        self::assertResponseStatusCodeSame($expectedStatusCode);

        // Applique une assertion personnalisée si fournie
        if (null !== $assertion) {
            $assertion();
        }
    }

    /**
     * Fournit les cas où un utilisateur non connecté ne doit pas accéder au formulaire ou soumettre une note.
     *
     * @return iterable<string, array{string, string, int, (callable|null)}>
     */
    public static function provideUnauthenticatedAccessScenarios(): iterable
    {
        yield 'GET - formulaire non affiché' => [
            'GET',
            '/jeu-video-0',
            Response::HTTP_OK,
            fn () => self::assertSelectorNotExists('form[name="review"]'),
        ];

        yield 'POST - refusé car non connecté' => [
            'POST',
            '/jeu-video-0',
            Response::HTTP_FOUND,
            null,
        ];
    }

    /**
     * @return iterable<string, array{rating: string|null, comment: string|null}>
     */
    public static function provideInvalidReviews(): iterable
    {
        yield 'Commentaire trop long' => [
            'rating' => '3',
            'comment' => str_repeat('a', 501),
        ];

        yield 'Note manquante' => [
            'rating' => null,
            'comment' => 'Pas de note',
        ];
    }
}
