<?php


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\SingleCommandApplication;
use Symfony\Component\HttpClient\HttpClient;

require __DIR__ . '/../vendor/autoload.php';


(new SingleCommandApplication())
    ->setName('app:weather')

    ->addArgument('lat', InputArgument::REQUIRED)
    ->addArgument('lng', InputArgument::REQUIRED)
    ->addOption('days','d', InputOption::VALUE_OPTIONAL, 'days', 7)
    ->setCode(function (InputInterface $input, OutputInterface $output): int {
        //  getting inputs ( arguments , options ... )
        $lat = (float)$input->getArgument('lat');
        $lng = (float)$input->getArgument('lng');
        $days = (int)$input->getOption('days');
        $output->writeln(sprintf(" lat: %s, lng:%s , days:%s ", $lat, $lng, $days ));

        //  asking question about temperature measurement unit
        $helper = $this->getHelper('question');
        $question = new Question("Do you prefer temperature in fahrenheit or celsius ? \n");
        $question->setAutocompleterValues(['fahrenheit', 'celsius']);
        $temperatureUnit = $helper->ask($input, $output, $question);
        $output->writeln(sprintf(" temperature will be in :%s ", $temperatureUnit ));

        // showing a progress bar
        $progressBar = new ProgressBar($output);
        $progressBar->start();
        sleep(1);
        $progressBar->setProgress(50);

        // fetching data from forecast api
        $response = (HttpClient::create())->request('GET',
            'https://api.open-meteo.com/v1/forecast',
            ['query' => [
                'latitude' => $lat,
                'longitude' => $lng,
                'daily' => 'temperature_2m_max,temperature_2m_min',
                'timezone' => 'Europe/Paris',
                'forecast_days' => $days,
                'temperature_unit' => $temperatureUnit,
            ]]
        )->toArray();

        // stopping progress bar
        $progressBar->setProgress(100);
        $progressBar->finish();
        $output->writeln('');

        // displaying data as table
        $table = new Table($output);
        $table->setHeaders(['Day', 'Temperature Min', 'Temperature Max']);
        $rows = [];
        foreach ($response['daily']['time'] as $key => $date) {
            $rows[] = [
                $date,
                $response['daily']['temperature_2m_min'][$key],
                $response['daily']['temperature_2m_max'][$key]
            ];

        }
        $table->setRows($rows);
        $table->render();


        return Command::SUCCESS;
    })
    ->run();