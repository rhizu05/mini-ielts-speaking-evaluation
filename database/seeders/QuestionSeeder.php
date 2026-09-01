<?php

namespace Database\Seeders;

use App\Models\Question;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $questions = [
            // Part 1 - Interview singkat (topik personal)
            ['part' => 1, 'topic' => 'Hometown', 'question_text' => 'Where is your hometown, and what do you like most about it?'],
            ['part' => 1, 'topic' => 'Hobbies', 'question_text' => 'What do you usually do in your free time?'],
            ['part' => 1, 'topic' => 'Food', 'question_text' => 'What kind of food is popular in your country?'],
            ['part' => 1, 'topic' => 'Work/Study', 'question_text' => 'Do you work or are you a student?'],

            // Part 2 - Long turn (cue card, 1-2 menit)
            ['part' => 2, 'topic' => 'Hometown', 'question_text' => 'Describe a place in your hometown that is special to you. You should say where it is, what it looks like, and explain why it is special.'],
            ['part' => 2, 'topic' => 'Technology', 'question_text' => 'Describe a piece of technology you use every day. You should say what it is, how you use it, and why it is important to you.'],
            ['part' => 2, 'topic' => 'People', 'question_text' => 'Describe a person who has influenced you. You should say who the person is, how you know them, and how they influenced you.'],
            ['part' => 2, 'topic' => 'Travel', 'question_text' => 'Describe a memorable trip you have taken. You should say where you went, who you went with, and why it was memorable.'],

            // Part 3 - Discussion (opini, lebih abstrak)
            ['part' => 3, 'topic' => 'Technology', 'question_text' => 'Do you think technology has made our lives easier or more complicated? Why?'],
            ['part' => 3, 'topic' => 'Education', 'question_text' => 'How has education changed in your country over the past 20 years?'],
            ['part' => 3, 'topic' => 'Environment', 'question_text' => 'What responsibility do individuals have to protect the environment?'],
            ['part' => 3, 'topic' => 'Work', 'question_text' => 'In the future, do you think people will work more from home? Why or why not?'],
        ];

        foreach ($questions as $question) {
            Question::create($question);
        }
    }
}
