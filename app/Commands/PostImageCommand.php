<?php

namespace App\Commands;

use Abraham\TwitterOAuth\TwitterOAuth;
use App\Data\ImageData;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use LaravelZero\Framework\Commands\Command;
use Woeler\DiscordPhp\Exception\DiscordInvalidResponseException;
use Woeler\DiscordPhp\Message\DiscordTextMessage;
use Woeler\DiscordPhp\Webhook\DiscordWebhook;

class PostImageCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'image:post {--dry}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Posts an image of Astolfo on Twitter and Discord.';

    /**
     * @var string
     */
    protected $astolfoBaseUrl;

    /**
     * @var int
     */
    protected $numberOfDaysUntilValidRepost;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->astolfoBaseUrl = env('ASTOLFO_BASE_URL');
        $this->numberOfDaysUntilValidRepost = env('NUMBER_OF_DAYS_UNTIL_VALID_REPOST', 365);

        $minimumCreatedAt = Carbon::now()
            ->subDays($this->numberOfDaysUntilValidRepost)
            ->startOfDay();

        $imageData = $this->getRandomImageData();

        $duplicatePostLogsCount = DB::table('post_logs')
            ->where('id', $imageData->getId())
            ->whereDate('created_at', '>=', $minimumCreatedAt->toDateString())
            ->count();

        if ($duplicatePostLogsCount > 0) {
            return $this->handle();
        }

        if (!$this->isDryRun()) {
            $this->postImageOnTwitterAndDiscord($imageData);
        }

        DB::table('post_logs')->insert([
            'image_id' => $imageData->getId(),
            'created_at' => Carbon::now(),
        ]);
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
        $sourceContent = "\n\nSource: {$imageData->getSource()}";

        return env('TWITTER_STATUS_HASHTAGS') . (empty($imageData->getSource()) ? '' : $sourceContent);
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
            $discordMessage = new DiscordTextMessage();
            $discordMessage->setUsername('Astolfo Image Poster');
            $discordMessage->setAvatar('https://i.imgur.com/W7Dv18c.jpg');
            $discordMessage->setContent($twitterUserPostUrl);

            $discordWebhook = new DiscordWebhook(env('DISCORD_WEBHOOK_URL'));
            $discordWebhook->send($discordMessage);
        } catch (DiscordInvalidResponseException $e) {
            Log::error("Couldn't post to Discord: {$twitterUserPostUrl}");
        }
    }

    private function isDryRun(): bool
    {
        return $this->option('dry');
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        $schedule->command(static::class)->daily()->at(env('POST_TIME'));
    }
}
