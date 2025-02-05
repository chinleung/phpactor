<?php

namespace Phpactor\Extension\ClassMover\Command;

use Phpactor\Extension\SourceCodeFilesystem\SourceCodeFilesystemExtension;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Phpactor\Extension\ClassMover\Application\ClassMover;
use Symfony\Component\Console\Input\InputArgument;
use Phpactor\Extension\ClassMover\Command\Logger\SymfonyConsoleMoveLogger;
use Symfony\Component\Console\Input\InputOption;
use Phpactor\Extension\Core\Console\Prompt\Prompt;
use Phpactor\Extension\Core\Console\Handler\FilesystemHandler;

class ClassMoveCommand extends Command
{
    const TYPE_AUTO = 'auto';
    const TYPE_CLASS = 'class';
    const TYPE_FILE = 'file';

    /**
     * @var ClassMover
     */
    private $mover;

    /**
     * @var Prompt
     */
    private $prompt;

    public function __construct(
        ClassMover $mover,
        Prompt $prompt
    ) {
        parent::__construct();
        $this->mover = $mover;
        $this->prompt = $prompt;
    }

    public function configure()
    {
        $this->setDescription('Move class (path or FQN) and update all references to it');
        $this->addArgument('src', InputArgument::REQUIRED, 'Source path or FQN');
        $this->addArgument('dest', InputArgument::OPTIONAL, 'Destination path or FQN');
        $this->addOption('type', null, InputOption::VALUE_REQUIRED, sprintf(
            'Type of move: "%s"',
            implode('", "', [self::TYPE_AUTO, self::TYPE_CLASS, self::TYPE_FILE])
        ), self::TYPE_AUTO);
        $this->addOption('related', null, InputOption::VALUE_NONE, 'Move related files (as defined by the patterns in navigator.destinations');
        FilesystemHandler::configure($this, SourceCodeFilesystemExtension::FILESYSTEM_GIT);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getOption('type');
        $logger = new SymfonyConsoleMoveLogger($output);
        $src = $input->getArgument('src');
        $dest = $input->getArgument('dest');
        $filesystem = $input->getOption('filesystem');
        $related = (bool) $input->getOption('related');

        if (null === $dest) {
            $dest = $this->prompt->prompt('Move to: ', $src);
        }

        switch ($type) {
            case 'auto':
                return $this->mover->move($logger, $filesystem, $src, $dest, $related);
            case 'file':
                return $this->mover->moveFile($logger, $filesystem, $src, $dest, $related);
            case 'class':
                return $this->mover->moveClass($logger, $filesystem, $src, $dest, $related);
        }

        throw new \InvalidArgumentException(sprintf(
            'Invalid type "%s", must be one of: "%s"',
            $type,
            implode('", "', [ self::TYPE_AUTO, self::TYPE_FILE, self::TYPE_CLASS ])
        ));
    }
}
