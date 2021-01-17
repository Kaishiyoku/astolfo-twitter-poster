<?php

namespace App\Console\Commands;

use App\Libraries\ImageData;
use Illuminate\Support\Facades\Http;
use Log;
use Abraham\TwitterOAuth\TwitterOAuth;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
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
        $db = app('db');

        $dryRun = $this->option('dry');

        $minimumDate = Carbon::now()->subDays(env('NUMBER_OF_DAYS_UNTIL_VALID_REPOST'))->startOfDay();
        $imageData = $this->getRandomImageData();

        $postLogs = $db->table('post_logs')
            ->where('external_id', $imageData->getExternalId())
            ->whereDate('created_at', '>=', $minimumDate->toDateString());

        if ($postLogs->count() > 0) {
            return $this->handle();
        }

        if (!$dryRun) {
            $temporaryFile = tmpfile();
            fwrite($temporaryFile, $imageData->getImageFileData());
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

        $db->table('post_logs')->insert([
            'external_id' => $imageData->getExternalId(),
            'created_at' => Carbon::now(),
        ]);
    }

    /**
     * @return ImageData
     */
    private function getRandomImageData(): ImageData
    {
        $jsonData = Http::get('https://astolfo.rocks/api/v1/images/random/safe')->json();

        $imageData = ImageData::fromJson($jsonData);
        $imageData->setImageFileData(Http::get("https://astolfo.rocks/api/v1/images/{$jsonData['external_id']}/data")->body());

        return $imageData;
    }

    /**
     * @param $tweet
     * @return string
     */
    private function getTwitterUserPostUrlFor($tweet): string
    {
        $twitterUserName = env('TWITTER_USER_NAME');

        return "https://twitter.com/{$twitterUserName}/status/{$tweet->id}";
    }

    /**
     * @return TwitterOAuth
     */
    private function getTwitterConnection(): TwitterOAuth
    {
        return new TwitterOAuth(
            env('TWITTER_API_CONSUMER_KEY'),
            env('TWITTER_AP_CONSUMER_SECRET_KEY'),
            env('TWITTER_API_ACCESS_TOKEN'),
            env('TWITTER_API_ACCESS_TOKEN_SECRET')
        );
    }

    /**
     * @param ImageData $imageData
     * @return string
     */
    private function getTwitterStatusContent(ImageData $imageData): string
    {
        return env('ASTOLFO_IMAGE_DETAILS_BASE_URL') . $imageData->getExternalId() . " \n "
        . "\n"
        . env('TWITTER_STATUS_HASHTAGS');
    }
}
