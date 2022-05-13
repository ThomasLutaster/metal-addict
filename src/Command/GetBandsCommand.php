<?php

namespace App\Command;

use App\Entity\Band;
use App\Repository\BandRepository;
use App\Service\MusicbrainzApiGetDatas;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:get-bands',
    description: 'Get bands using Musicbrainz Api',
)]
class GetBandsCommand extends Command
{
    public function __construct(private MusicbrainzApiGetDatas $musicbrainzApiGetDatas, private EntityManagerInterface $em, private BandRepository $bandRepository)
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


        // Modify offset and loop parameters to get more bands
        for ($offset = 1; $offset < 5002; $offset += 100) {
            $bands = $this->musicbrainzApiGetDatas->getMusicbrainzBands($offset);

            foreach ($bands['artists'] as $apiBand) {

                if ($this->bandRepository->findOneBy(['musicbrainzId' => $apiBand['id']]) === null) {
                    $band = new Band();

                    $band->setMusicbrainzId($apiBand['id']);
                    $band->setName($apiBand['name']);

                    $this->em->persist($band);
                }
            }
            $this->em->flush();
            usleep(1000000);
        }



        $io->success('All bands are saved in database');

        return Command::SUCCESS;
    }
}
