<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Abraham\TwitterOAuth\TwitterOAuth;

class PostImage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'astolfo:post';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Posts an image of Astolfo on Twitter.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $imageData = json_decode(file_get_contents('https://astolfo.rocks/api/v1/images/random/safe'));

        $temporaryFile = tmpfile();
        fwrite($temporaryFile, file_get_contents($imageData->url));
        $temporaryFileMetaData = stream_get_meta_data($temporaryFile);

        $connection = new TwitterOAuth(env('TWITTER_API_CONSUMER_KEY'), env('TWITTER_AP_CONSUMER_SECRET_KEY'), env('TWITTER_API_ACCESS_TOKEN'), env('TWITTER_API_ACCESS_TOKEN_SECRET'));

        $imageMedia = $connection->upload('media/upload', array('media' => $temporaryFileMetaData['uri']));

        $status = env('ASTOLFO_IMAGE_DETAILS_BASE_URL') . $imageData->external_id . " \n "
            . "\n"
            . env('TWITTER_STATUS_HASHTAGS');

        $tweet = $connection->post('statuses/update', [
            'status' => $status,
            'media_ids' => $imageMedia->media_id,
        ]);
    }
}