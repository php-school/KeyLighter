<?php
/**
 * Highlighter
 *
 * Copyright (C) 2016, Some right reserved.
 *
 * @author Kacper "Kadet" Donat <kacper@kadet.net>
 *
 * Contact with author:
 * Xmpp: me@kadet.net
 * E-mail: contact@kadet.net
 *
 * From Kadet with love.
 */

namespace Kadet\Highlighter\bin\Commands\Dev;


use Kadet\Highlighter\KeyLighter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateTableCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if($input->getOption('dry')) {
            $output->writeln($this->generate());
        } else {
            $path = __DIR__ . '/../../../Docs/languages.md';

            $output->writeln("<info>Opening file ./Docs/languages.md ...</info>", OutputInterface::VERBOSITY_VERBOSE);
            $content = file_get_contents($path);
            file_put_contents($path, preg_replace(
                '/^<!-- aliasbegin -->\R.*?^<!-- aliasend -->\R/ms',
                "<!-- aliasbegin -->\n".$this->generate()."<!-- aliasend -->\n",
                $content
            ));
            $output->writeln("<info>Closing file ./Docs/languages.md ...</info>", OutputInterface::VERBOSITY_VERBOSE);
        }
    }

    protected function configure()
    {
        $this
            ->setName('dev:generate-table')
            ->setDescription('Generates language table for documentation')
            ->addOption('dry', 'd', InputOption::VALUE_NONE, 'Dry run (output table to stdout)')
        ;
    }

    protected function generate()
    {
        $result = [];
        foreach(['name', 'mime', 'extension'] as $what) {
            foreach(KeyLighter::get()->registeredLanguages($what, true) as $name => $class) {
                $result[$class][$what][] = $name;
            }
        }

        $return  =  'Class | Name | MIME | Extension'.PHP_EOL;
        $return .=  '------|------|------|----------'.PHP_EOL;
        foreach($result as $class => $aliases) {
            $return .= '`'.$class.'` | ';
            $return .= (isset($aliases['name']) ? '`'.implode('`, `', $aliases['name']).'`' : 'none').' | ';
            $return .= (isset($aliases['mime']) ? '`'.implode('`, `', $aliases['mime']).'`' : 'none').' | ';
            $return .= (isset($aliases['extension']) ? '`'.implode('`, `', $aliases['extension']).'`' : 'none'). PHP_EOL;
        }

        return $return;
    }
}
