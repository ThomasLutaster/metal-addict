<?php

namespace App\Command;

use App\Entity\Country;
use App\Service\SetlistApiGetDatas;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:get-countries',
    description: 'Get countries using setList API',
)]
class GetCountriesCommand extends Command
{
    public function __construct(private SetlistApiGetDatas $setlistApiGetDatas, private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('arg1');

        if ($arg1) {
            $io->note(sprintf('You passed an argument: %s', $arg1));
        }

        if ($input->getOption('option1')) {
            // ...
        }

        $countries = $this->setlistApiGetDatas->getApiSetlistCountries();

        foreach ($countries["country"] as $apiCountry) {
            $country = new Country();

            $country->setName($apiCountry["name"]);
            $country->setCountryCode($apiCountry["code"]);

            $this->em->persist($country);
        }

        $this->em->flush();

        $io->success('All countries are save in database');

        return Command::SUCCESS;
    }
}
