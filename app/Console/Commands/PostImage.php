<?php

namespace App\Console\Commands;

use Log;
use Abraham\TwitterOAuth\TwitterOAuth;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Woeler\DiscordPhp\Exception\DiscordInvalidResponseException;
use Woeler\DiscordPhp\Message\DiscordTextMessage;
use Woeler\DiscordPhp\Webhook\DiscordWebhook;

class PostImage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'astolfo:post {--dry}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Posts an image of Astolfo on Twitter and Discord.';

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
        $dryRun = $this->option('dry');

        $minimumDate = Carbon::now()->subDays(env('NUMBER_OF_DAYS_UNTIL_VALID_REPOST'))->startOfDay();
        $imageData = $this->getRandomImageData();
        $postLogs = DB::table('post_logs')
            ->where('external_id', $imageData->external_id)
            ->whereDate('created_at', '>=', $minimumDate->toDateString());

        if ($postLogs->count() > 0) {
            return $this->handle();
        }

        if (!$dryRun) {
            $temporaryFile = tmpfile();
            fwrite($temporaryFile, file_get_contents($imageData->url));
            $temporaryFileMetaData = stream_get_meta_data($temporaryFile);

            $connection = $this->getTwitterConnection();
            $imageMedia = $connection->upload('media/upload', ['media' => $temporaryFileMetaData['uri']]);

            $tweet = $connection->post('statuses/update', [
                'status' => $this->getTwitterStatusContent($imageData),
                'media_ids' => $imageMedia->media_id,
            ]);

            try {
                $message = (new DiscordTextMessage())->setContent($this->getTwitterUserPostUrlFor($tweet));

                $webhook = new DiscordWebhook(env('DISCORD_WEBHOOK_URL'));
                $webhook->send($message);
            } catch (DiscordInvalidResponseException $e) {
                Log::error("Couldn't post to Discord: {$this->getTwitterUserPostUrlFor($tweet)}");
            }
        }

        DB::table('post_logs')->insert([
            'external_id' => $imageData->external_id,
            'created_at' => Carbon::now(),
        ]);
    }

    /**
     * @return mixed
     */
    private function getRandomImageData()
    {
        $client = new Client();
        $response = $client->get('https://astolfo.rocks/api/v1/images/random/safe');

        return json_decode($response->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param $tweet
     * @return string
     */
    private function getTwitterUserPostUrlFor($tweet)
    {
        $twitterUserName = env('TWITTER_USER_NAME');

        return "https://twitter.com/{$twitterUserName}/status/{$tweet->id}";
    }

    /**
     * @return TwitterOAuth
     */
    private function getTwitterConnection()
    {
        return new TwitterOAuth(
            env('TWITTER_API_CONSUMER_KEY'),
            env('TWITTER_AP_CONSUMER_SECRET_KEY'),
            env('TWITTER_API_ACCESS_TOKEN'),
            env('TWITTER_API_ACCESS_TOKEN_SECRET')
        );
    }

    /**
     * @param $imageData
     * @return string
     */
    private function getTwitterStatusContent($imageData)
    {
        return env('ASTOLFO_IMAGE_DETAILS_BASE_URL') . $imageData->external_id . " \n "
        . "\n"
        . env('TWITTER_STATUS_HASHTAGS');
    }
}
