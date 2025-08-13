<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Model\Entity\Review;
use App\Model\Entity\VideoGame;
use App\Rating\RatingHandler;
use PHPUnit\Framework\TestCase;

final class CalculateAverageRatingTest extends TestCase
{
    /**
     * @dataProvider provideVideoGame
     */
    public function testShouldCalculateAverageRating(VideoGame $videoGame, ?int $expectedAverageRating): void
    {
        // Arrange : instanciation du service de calcul de notes
        $ratingHandler = new RatingHandler();

        // Act : calcule la moyenne des notes pour le jeu fourni
        $ratingHandler->calculateAverage($videoGame);

        // Assert : la moyenne calculée doit correspondre à la valeur attendue
        self::assertSame($expectedAverageRating, $videoGame->getAverageRating());
    }

    /**
     *  Fournit différents cas de jeux vidéo pour tester le calcul de la moyenne :
     * - Sans aucune note
     * - Avec une seule note
     * - Avec plusieurs notes (moyenne attendue : 4)
     *
     * @return iterable<array{VideoGame, ?int}>
     */
    public static function provideVideoGame(): iterable
    {
        yield 'No review' => [new VideoGame(), null];

        yield 'One review' => [self::createVideoGame(5), 5];

        yield 'A lot of reviews' => [
            self::createVideoGame(1, 2, 2, 3, 3, 3, 4, 4, 4, 4, 5, 5, 5, 5, 5),
            4,
        ];
    }

    private static function createVideoGame(int ...$ratings): VideoGame
    {
        // Instancie un jeu vidéo vide
        $videoGame = new VideoGame();

        // Ajoute chaque note en tant qu'avis au jeu
        foreach ($ratings as $rating) {
            $videoGame->getReviews()->add((new Review())->setRating($rating));
        }

        return $videoGame;
    }
}
