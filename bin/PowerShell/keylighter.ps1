param([string]$script:keylighter = $(Join-Path $PSScriptRoot '../keylighter'));

$script:esc = [char]27

function Show-HighlightedSource {
    param(
        [Parameter(
            Position=0,
            ValueFromPipeline=$true,
            parametersetname='nopipeline'
        )][string]$File,

        [Parameter(
            Position=1,
            ValueFromPipelineByPropertyName=$true
        )]
        [Alias('l','Lang')]
        [string]$Language,

        [Parameter(
            Position=2,
            ValueFromPipelineByPropertyName=$true
        )]
        [Alias('f','Format')]
        [string]$Formatter,

        [Alias('s')][switch]$Silent,

        [Alias('v')][int]$VerboseLevel = 0
    )

    Begin {
        $pipeline = -not ($PSCmdlet.ParameterSetName -eq 'nopipeline');
    }

    Process {
        $source += $_ + "`n";
    }

    End {
        $params = @();

        if($Language) {
            $params += @('-l', $language)
        }

        if($Formatter) {
            $params += @('-f', $formatter)
        }

        if($Silent) {
            $params += @('-s')
        }

        if($VerboseLevel -gt 0) {
            $params += @('-v', $VerboseLevel)
        }

        if(!$pipeline) {
            php $script:keylighter $File @params;
        } else   {
            if($file) {
                $source | php $keylighter php://stdin @params;
            } else {
                php $keylighter
            }
        }
    }

    <#
        .Synopsis
            Shows highlighted source using KeyLigter.

        .Parameter File
            File name of file to highlight.

        .Parameter Language
            Highlighting language, use Get-KeyLighterLanguages to list available Languages.
            You can use 'parent > child' syntax to indicate language embedding. For example
            'html > php' means PHP embedded in HTML.

            Default: 'html > php'

        .Parameter Formatter
            Formatter used to output highlighted file, use Get-KeyLighterFormatter to list available formatters.

            Default: 'cli'

        .Parameter Silent
            Turns off output

            Default: false

        .Parameter VerboseLevel
            Level of output verbosity.

                0: No verbose, just highlighted text,
                1: Basic informations, used Language, Formatter and File with size,
                2: Parsing times,
                3: Processed Token Tree
                4: Unprocessed Tokens

            Default: 0
    #>
}

function Get-KeyLighterLanguages {
    php $script:keylighter --languages | % {
        if($_ -match '(\w+(?:, \w+)*)\s*=>\s*([\w\\]+)') {
            New-Object psobject -Property @{
                Aliases = $Matches[1].Trim().Split(', ', [System.StringSplitOptions]::RemoveEmptyEntries);
                Class = $Matches[2];
            }
        }
    }

    <#
        .Synopsis
             Gets all available highlgihting languages.

        .Example
            PS> Get-KeyLighterLanguages
            
            Class                                 Aliases
            -----                                 -------
            Kadet\Highlighter\Language\Php        {php}
            Kadet\Highlighter\Language\Xml        {xml, xaml}
            ...
    #>
}

function Get-KeyLighterFormatters {
    php $script:keylighter --formatters | % {
        if($_ -match "$script:esc\[33m(\w+)$script:esc\[0m\s+([\w\\]+)") {
            New-Object psobject -Property @{
                Name  = $Matches[1];
                Class = $Matches[2];
            }
        }
    }

    <#
        .Synopsis
             Gets all available highlgihting formatters.

        .Example
            PS> Get-KeyLighterFormatters
            
            Class                                      Name
            -----                                      ----
            Kadet\Highlighter\Formatter\HtmlFormatter  html
            Kadet\Highlighter\Formatter\CliFormatter   cli
            ...
    #>
}

if (Get-Command Register-ArgumentCompleter -ea Ignore) {
    Register-ArgumentCompleter -Command Show-HighlightedSource -Parameter Language -ScriptBlock {
        param($commandName, $parameterName, $wordToComplete, $commandAst, $fakeBoundParameter);

        Get-KeyLighterLanguages | % { 
            $class = $_.Class; 
            foreach($alias in $_.Aliases) {
                if($alias -like "$wordToComplete*") {
                    New-CompletionResult -CompletionText $alias -ToolTip $class
                }
            } 
        }
    }

    Register-ArgumentCompleter -Command Show-HighlightedSource -Parameter Formatter -ScriptBlock {
        param($commandName, $parameterName, $wordToComplete, $commandAst, $fakeBoundParameter);

        Get-KeyLighterFormatters | Where-Object { $_.Name -like "$wordToComplete*" } | % {
            New-CompletionResult -CompletionText $_.Name -ToolTip $_.Class
        }
    }
}

# Aliases
Set-Alias KeyLighter Show-HighlightedSource
Set-Alias kl         Show-HighlightedSource