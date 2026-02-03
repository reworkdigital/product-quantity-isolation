<?php

namespace Database\Seeders\Concerns;

use Symfony\Component\Console\Helper\ProgressBar;

trait WithProgressBar
{
    protected function progressEnabled(): bool
    {
        // Enable by default for CLI runs; allow disabling via env; auto-disable in tests
        $env = getenv('SEED_SHOW_PROGRESS');
        $enabled = $env === false ? true : filter_var($env, FILTER_VALIDATE_BOOL);
        // `$this->command` is available only when running via Artisan
        return $enabled && property_exists($this, 'command') && $this->command !== null;
    }

    protected function progressStart(int $total, ?string $message = null): ?ProgressBar
    {
        if (!$this->progressEnabled()) {
            return null;
        }

        $output = $this->command->getOutput();
        if ($message) {
            $output->writeln($message);
        }

        $bar = new ProgressBar($output, max(0, $total));
        $bar->setFormat(' %message% [%bar%] %percent:3s%% (%current%/%max%) %elapsed:6s%');
        $bar->setMessage('');
        $bar->start();
        return $bar;
    }

    protected function progressSetMessage(?ProgressBar $bar, string $message): void
    {
        if ($bar) {
            $bar->setMessage($message);
        }
    }

    protected function progressAdvance(?ProgressBar $bar, int $steps = 1): void
    {
        if ($bar) {
            $bar->advance($steps);
        }
    }

    protected function progressFinish(?ProgressBar $bar, ?string $suffix = null): void
    {
        if ($bar) {
            $bar->finish();
            $this->command->getOutput()->writeln($suffix ? "\n{$suffix}" : "\n");
        }
    }
}
