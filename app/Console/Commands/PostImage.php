<?php

namespace App\Console\Commands;

use Abraham\TwitterOAuth\TwitterOAuth;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RestCord\DiscordClient;

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
        $dryRun = $this->option('dry');

        $minimumDate = Carbon::now()->subDays(env('NUMBER_OF_DAYS_UNTIL_VALID_REPOST'))->startOfDay();
        $imageData = json_decode(file_get_contents('https://astolfo.rocks/api/v1/images/random/safe'));
        $postLogs = DB::table('post_logs')->where('external_id', $imageData->external_id)->whereDate('created_at', '>=', $minimumDate->toDateString());

        if ($postLogs->count() > 0) {
            return $this->handle();
        }

        if (!$dryRun) {
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

            $discordClient = new DiscordClient(['token' => env('DISCORD_BOT_TOKEN')]);

            $discordClient->channel->createMessage([
                'channel.id' => (int) env('DISCORD_CHANNEL_ID'),
                'content' => 'https://twitter.com/Astolfo_is_luv/status/' . $tweet->id,
            ]);
        }

        DB::table('post_logs')->insert([
            'external_id' => $imageData->external_id,
            'created_at' => Carbon::now(),
        ]);
    }
}