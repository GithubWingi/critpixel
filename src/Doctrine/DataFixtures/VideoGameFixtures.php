<?php

namespace App\Doctrine\DataFixtures;

use App\Model\Entity\Review;
use App\Model\Entity\Tag;
use App\Model\Entity\User;
use App\Model\Entity\VideoGame;
use App\Rating\CalculateAverageRating;
use App\Rating\CountRatingsPerValue;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;

final class VideoGameFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly Generator $faker,
        private readonly CalculateAverageRating $calculateAverageRating,
        private readonly CountRatingsPerValue $countRatingsPerValue,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $users = array_chunk(
            $manager->getRepository(User::class)->findAll(),
            5
        );
        $groupCount = count($users);

        $tags = $manager->getRepository(Tag::class)->findAll();

        // Création et persistance des jeux vidéos
        for ($i = 0; $i < 50; ++$i) {
            /** @var VideoGame $videoGame */
            $videoGame = (new VideoGame())
                ->setTitle(sprintf('Jeu vidéo %d', $i))
                ->setDescription($this->faker->paragraphs(10, true))
                ->setReleaseDate(new \DateTimeImmutable())
                ->setTest($this->faker->paragraphs(6, true))
                ->setRating(($i % 5) + 1)
                ->setImageName(sprintf('video_game_%d.png', $i))
                ->setImageSize(2_098_872);

            // Ajout des tags
            for ($tagIndex = 0; $tagIndex < 5; ++$tagIndex) {
                $videoGame->getTags()->add($tags[($i + $tagIndex) % count($tags)]);
            }

            $manager->persist($videoGame);

            // Ajout des reviews
            $filteredUsers = array_filter(
                $users[$i % $groupCount],
                fn (User $u) => 'user+0' !== $u->getUsername() // Exclure cet utilisateur
            );

            /** @var User $user */
            foreach ($filteredUsers as $user) {
                /** @var string $comment */
                $comment = $this->faker->paragraphs(1, true);

                $review = (new Review())
                    ->setUser($user)
                    ->setVideoGame($videoGame)
                    ->setRating($this->faker->numberBetween(1, 5))
                    ->setComment($comment);

                $videoGame->getReviews()->add($review);

                $manager->persist($review);

                $this->calculateAverageRating->calculateAverage($videoGame);
                $this->countRatingsPerValue->countRatingsPerValue($videoGame);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
