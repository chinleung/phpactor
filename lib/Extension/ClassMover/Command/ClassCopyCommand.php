<?php

namespace Phpactor\Extension\ClassMover\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Phpactor\Extension\ClassMover\Application\ClassCopy;
use Symfony\Component\Console\Input\InputArgument;
use Phpactor\Extension\ClassMover\Command\Logger\SymfonyConsoleCopyLogger;
use Symfony\Component\Console\Input\InputOption;
use Phpactor\Extension\Core\Console\Prompt\Prompt;

class ClassCopyCommand extends Command
{
    const TYPE_AUTO = 'auto';
    const TYPE_CLASS = 'class';
    const TYPE_FILE = 'file';

    /**
     * @var ClassCopy
     */
    private $copier;

    /**
     * @var Prompt
     */
    private $prompt;

    public function __construct(
        ClassCopy $copier,
        Prompt $prompt
    ) {
        parent::__construct();
        $this->copier = $copier;
        $this->prompt = $prompt;
    }

    public function configure()
    {
        $this->setDescription('Copy class (path or FQN)');
        $this->addArgument('src', InputArgument::REQUIRED, 'Source path or FQN');
        $this->addArgument('dest', InputArgument::OPTIONAL, 'Destination path or FQN');
        $this->addOption('type', null, InputOption::VALUE_REQUIRED, sprintf(
            'Type of copy: "%s"',
            implode('", "', [self::TYPE_AUTO, self::TYPE_CLASS, self::TYPE_FILE])
        ), self::TYPE_AUTO);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getOption('type');
        $logger = new SymfonyConsoleCopyLogger($output);
        $src = $input->getArgument('src');
        $dest = $input->getArgument('dest');

        if (null === $dest) {
            $dest = $this->prompt->prompt('Move to: ', $src);
        }

        switch ($type) {
            case 'auto':
                return $this->copier->copy($logger, $src, $dest);
            case 'file':
                return $this->copier->copyFile($logger, $src, $dest);
            case 'class':
                return $this->copier->copyClass($logger, $src, $dest);
        }

        throw new \InvalidArgumentException(sprintf(
            'Invalid type "%s", must be one of: "%s"',
            $type,
            implode('", "', [ self::TYPE_AUTO, self::TYPE_FILE, self::TYPE_CLASS ])
        ));
    }
}
