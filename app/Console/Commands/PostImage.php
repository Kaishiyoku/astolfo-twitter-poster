<?php

namespace App\Console\Commands;

use Abraham\TwitterOAuth\TwitterOAuth;
use App\Data\ImageData;
use App\Models\PostLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
    protected $signature = 'image:post {--dry}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Posts an image of Astolfo on Twitter and Discord';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $minimumCreatedAt = Carbon::now()
            ->subDays(config('astolfo.number_of_days_until_valid_repost'))
            ->startOfDay();

        $imageData = $this->getRandomImageData();

        $duplicatePostLogsCount = PostLog::query()
            ->where('id', $imageData->getId())
            ->whereDate('created_at', '>=', $minimumCreatedAt)
            ->count();

        if ($duplicatePostLogsCount > 0) {
            return $this->handle();
        }

        if (!$this->isDryRun()) {
            $this->postImageOnTwitterAndDiscord($imageData);
        }

        PostLog::create([
            'image_id' => $imageData->getId(),
        ]);

        return 0;
    }

    /**
     * @return ImageData
     */
    private function getRandomImageData(): ImageData
    {
        $baseUrl = config('astolfo.base_url');
        $jsonData = Http::get("{$baseUrl}/api/v1/images/random/safe")->json();

        $imageData = ImageData::fromJson($jsonData);

        $imageData->setImageFileData(Http::get("{$baseUrl}/astolfo/{$imageData->getId()}.{$imageData->getFileExtension()}")->body());

        return $imageData;
    }

    /**
     * @param $tweet
     * @return string
     */
    private function getTwitterUserPostUrlForTweet($tweet): string
    {
        $twitterUserName = config('twitter.user_name');

        return "https://twitter.com/{$twitterUserName}/status/{$tweet->id}";
    }

    private function getTwitterConnection(): TwitterOAuth
    {
        return new TwitterOAuth(
            config('twitter.api_consumer_key'),
            config('twitter.api_consumer_secret_key'),
            config('twitter.api_access_token'),
            config('twitter.api_access_token_secret')
        );
    }

    private function getTwitterStatusContent(ImageData $imageData): string
    {
        $sourceContent = "\n\nSource: {$imageData->getSource()}";

        return config('twitter.status_hashtags') . (empty($imageData->getSource()) ? '' : $sourceContent);
    }

    private function postImageOnTwitterAndDiscord(ImageData $imageData): void
    {
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
            $discordMessage = new DiscordTextMessage();
            $discordMessage->setUsername('Astolfo Image Poster');
            $discordMessage->setAvatar('https://i.imgur.com/W7Dv18c.jpg');
            $discordMessage->setContent($twitterUserPostUrl);

            $discordWebhook = new DiscordWebhook(config('discord.webhook_url'));
            $discordWebhook->send($discordMessage);
        } catch (DiscordInvalidResponseException $e) {
            Log::error("Couldn't post to Discord: {$twitterUserPostUrl}");
        }
    }

    private function isDryRun(): bool
    {
        return $this->option('dry');
    }
}
