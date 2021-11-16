<?php

namespace App\Console\Commands;

use Abraham\TwitterOAuth\TwitterOAuth;
use App\Data\ImageData;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Log;
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
     * @var DatabaseManager
     */
    protected DatabaseManager $db;

    /**
     * @var string
     */
    protected $astolfoBaseUrl;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(DatabaseManager $db)
    {
        parent::__construct();

        $this->db = $db;
        $this->astolfoBaseUrl = env('ASTOLFO_BASE_URL');
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

        $duplicatePostLogs = $this->db->table('post_logs')
            ->where('id', $imageData->getId())
            ->whereDate('created_at', '>=', $minimumDate->toDateString());

        if ($duplicatePostLogs->count() > 0) {
            return $this->handle();
        }

        if (!$dryRun) {
            $this->postImageOnTwitterAndDiscord($imageData);
        }

        $this->db->table('post_logs')->insert([
            'image_id' => $imageData->getId(),
            'created_at' => Carbon::now(),
        ]);

        return 0;
    }

    /**
     * @return ImageData
     */
    private function getRandomImageData(): ImageData
    {
        $jsonData = Http::get("{$this->astolfoBaseUrl}/api/v1/images/random/safe")->json();

        $imageData = ImageData::fromJson($jsonData);

        $imageData->setImageFileData(Http::get("{$this->astolfoBaseUrl}/astolfo/{$imageData->getId()}.{$imageData->getFileExtension()}")->body());

        return $imageData;
    }

    /**
     * @param $tweet
     * @return string
     */
    private function getTwitterUserPostUrlForTweet($tweet): string
    {
        $twitterUserName = env('TWITTER_USER_NAME');

        return "https://twitter.com/{$twitterUserName}/status/{$tweet->id}";
    }

    private function getTwitterConnection(): TwitterOAuth
    {
        return new TwitterOAuth(
            env('TWITTER_API_CONSUMER_KEY'),
            env('TWITTER_AP_CONSUMER_SECRET_KEY'),
            env('TWITTER_API_ACCESS_TOKEN'),
            env('TWITTER_API_ACCESS_TOKEN_SECRET')
        );
    }

    private function getTwitterStatusContent(ImageData $imageData): string
    {
        return env('TWITTER_STATUS_HASHTAGS');
    }

    private function postImageOnTwitterAndDiscord(ImageData $imageData): void {
        $temporaryFile = tmpfile();
        fwrite($temporaryFile, $imageData->getImageFileData());
        $temporaryFileMetaData = stream_get_meta_data($temporaryFile);

        $twitterConnection = $this->getTwitterConnection();
        $imageMedia = $twitterConnection->upload('media/upload', ['media' => Arr::get($temporaryFileMetaData, 'uri')]);

        $tweet = $twitterConnection->post('statuses/update', [
            'status' => $this->getTwitterStatusContent($imageData),
            'media_ids' => $imageMedia->media_id,
        ]);

        $twitterUserPostUrl = $this->getTwitterUserPostUrlForTweet($tweet);

        try {
            $content = $twitterUserPostUrl . (empty($imageData->getSource()) ? '' : $sourceContent);

            $discordMessage = new DiscordTextMessage();
            $discordMessage->setUsername('Astolfo Image Poster');
            $discordMessage->setAvatar('https://i.imgur.com/W7Dv18c.jpg');
            $discordMessage->setContent($content);

            $discordWebhook = new DiscordWebhook(env('DISCORD_WEBHOOK_URL'));
            $discordWebhook->send($discordMessage);
        } catch (DiscordInvalidResponseException $e) {
            Log::error("Couldn't post to Discord: {$twitterUserPostUrl}");
        }
    }
}
