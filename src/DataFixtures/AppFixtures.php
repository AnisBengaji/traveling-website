<?php namespace App\DataFixtures;

use App\Entity\Publication;
use App\Entity\Comment;
use App\Entity\Category;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory as Faker;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Initialize Faker
        $faker = Faker::create();

        // Create a user with ID 5 manually
        $user = new User();
        $user->setNom('John')
             ->setPrenom('Doe')
             ->setEmail('johndoe@example.com')
             ->setMdp('password') // Make sure to hash the password later if using Symfony security
             ->setRole('client') // Set the role
             ->setNumTel(123456789);

        $manager->persist($user); // Persist user to get the correct ID (this will assign ID 5)
        $manager->flush(); // Flush to ensure the user is written to the database

        // Generate 5 fake categories
        $categories = []; // Store the categories in an array to use them later
        for ($i = 0; $i < 7; $i++) {
            $category = new Category();
            $category->setNomCategory($faker->word)
                     ->setDescription('Some description here');
            $manager->persist($category);
            $categories[] = $category; // Save the category to the array
        }

        $manager->flush(); // Flush categories to ensure they are saved

        // Generate 10 fake publications
        for ($i = 0; $i < 10; $i++) {
            $publication = new Publication();
            $publication->setTitle($faker->sentence)
                        ->setContenu($faker->paragraph)
                        ->setDatePublication($faker->dateTimeThisYear)
                        ->setVisibility($faker->randomElement(['public', 'private']))
                        ->setCategory($faker->randomElement($categories)) // Assign a random category from the array
                        ->setImage($faker->imageUrl())
                        ->setAuthor($user); // Set the created user as the author

            $manager->persist($publication);

            // Generate 3 fake comments for each publication
            for ($j = 0; $j < 5; $j++) {
                $comment = new Comment();
                $comment->setAuthor($user) // Set the same user as the comment's author
                        ->setContent($faker->text)
                        ->setDateComment($faker->dateTimeThisYear) // Use the correct method name
                        ->setPublication($publication);

                $manager->persist($comment);
            }
        }

        $manager->flush(); // Final flush to persist all publications and comments
    }
}
