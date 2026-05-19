<?php

namespace App\Command;

use App\Service\TickService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:tick',
    description: 'Process timed events: punishment countdowns, ad expiry, session cleanup. Run every minute via task scheduler.'
)]
class TickCommand extends Command
{
    public function __construct(private readonly TickService $tickService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'daemon',
            'd',
            InputOption::VALUE_NONE,
            'Run continuously (every 60 seconds) instead of once. Use for development only.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('daemon')) {
            $io->note('Running in daemon mode. Press Ctrl+C to stop.');
            while (true) {
                $this->runOnce($io);
                sleep(60);
            }
        }

        $this->runOnce($io);

        return Command::SUCCESS;
    }

    private function runOnce(SymfonyStyle $io): void
    {
        $start = microtime(true);
        $stats = $this->tickService->tick();
        $ms    = round((microtime(true) - $start) * 1000, 1);

        $io->success(sprintf(
            '[%s] Tick done in %s ms | bans_lifted=%d ad_bans=%d mutes=%d staff_bans=%d ads_expired=%d sessions=%d codes=%d',
            date('Y-m-d H:i:s'),
            $ms,
            $stats['bans_lifted'],
            $stats['ad_bans_lifted'],
            $stats['mutes_lifted'],
            $stats['staff_bans_lifted'],
            $stats['ads_expired'],
            $stats['sessions_cleaned'],
            $stats['codes_cleaned']
        ));
    }
}
