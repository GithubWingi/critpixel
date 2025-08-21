<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Model\Entity\NumberOfRatingPerValue;
use App\Model\Entity\Review;
use App\Model\Entity\VideoGame;
use App\Rating\RatingHandler;
use PHPUnit\Framework\TestCase;

final class CountRatingsPerValueTest extends TestCase
{
    /**
     * @dataProvider provideVideoGame
     *
     * Teste le calcul du nombre de votes pour chaque valeur de note (1 à 5).
     */
    public function testShouldCountRatingPerValue(VideoGame $videoGame, NumberOfRatingPerValue $expectedNumberOfRatingPerValue): void
    {
        // Arrange : instanciation du service de calcul de notes
        $ratingHandler = new RatingHandler();

        // Act : calcule le nombre de votes par valeur de note pour le jeu
        $ratingHandler->countRatingsPerValue($videoGame);

        // Assert : les résultats doivent correspondre à ceux attendus
        self::assertEquals($expectedNumberOfRatingPerValue, $videoGame->getNumberOfRatingsPerValue());
    }

    /**
     * Fournit différents scénarios pour tester le comptage des votes par valeur de note :
     * - Aucun avis
     * - Un seul avis
     * - Plusieurs avis répartis sur toutes les notes (1 à 5)
     *
     * @return iterable<array{VideoGame, NumberOfRatingPerValue}>
     */
    public static function provideVideoGame(): iterable
    {
        yield 'No review' => [
            new VideoGame(),
            new NumberOfRatingPerValue(),
        ];

        yield 'One review' => [
            self::createVideoGame(5),
            self::createExpectedState(five: 1),
        ];

        yield 'A lot of reviews' => [
            self::createVideoGame(1, 2, 2, 3, 3, 3, 4, 4, 4, 4, 5, 5, 5, 5, 5),
            self::createExpectedState(1, 2, 3, 4, 5),
        ];
    }

    private static function createVideoGame(int ...$ratings): VideoGame
    {
        // Instancie un jeu vidéo vide
        $videoGame = new VideoGame();

        // Ajoute chaque avis pour le jeu
        foreach ($ratings as $rating) {
            $videoGame->getReviews()->add((new Review())->setRating($rating));
        }

        return $videoGame;
    }

    /**
     * Crée un état attendu pour le nombre de votes par valeur de note.
     */
    private static function createExpectedState(int $one = 0, int $two = 0, int $three = 0, int $four = 0, int $five = 0): NumberOfRatingPerValue
    {
        $state = new NumberOfRatingPerValue();

        for ($i = 0; $i < $one; ++$i) {
            $state->increaseOne();
        }

        for ($i = 0; $i < $two; ++$i) {
            $state->increaseTwo();
        }

        for ($i = 0; $i < $three; ++$i) {
            $state->increaseThree();
        }

        for ($i = 0; $i < $four; ++$i) {
            $state->increaseFour();
        }

        for ($i = 0; $i < $five; ++$i) {
            $state->increaseFive();
        }

        return $state;
    }
}
