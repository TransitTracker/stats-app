<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use League\Csv\Reader;

class ImportStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import stats to the database using a CSV file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        LazyCollection::make(function () {
            $handle = fopen(storage_path('stats.csv'), 'r');

            while (($line = fgetcsv($handle, 4096)) !== false) {
                $dataString = implode(', ', $line);
                $row = explode(', ', $dataString);
                yield $row;
            }

            fclose($handle);
        })
            ->skip(1)
            ->chunk(1000)
            ->each(function (LazyCollection $chunk) {
                $records = $chunk->map(function ($row) {
                    return [
                        'time' => $row[0],
                        'agency' => $row[1],
                        'count' => $row[2],
                    ];
                })->toArray();

                DB::table('stats')->insert($records);
            });


        /* $csv = Reader::createFromPath($this->argument('file'), 'r');
        $csv->setHeaderOffset(0);

        $this->info('Now reading CSV file...');

        $stats = [];

        foreach ($csv->getRecords() as $record) {
            $stats[] = $record;
        }

        $this->info('Now inserting data...');
        $bar = $this->output->createProgressBar(count($stats) / 100_000);

        $chunks = array_chunk($stats, 100_000);

        foreach ($chunks as $chunk) {
            DB::insert('insert into stats (time, agency, count) values (?, ?, ?)', $chunk);
            $bar->advance();
        }

        $bar->finish(); */

        return Command::SUCCESS;
    }
}
