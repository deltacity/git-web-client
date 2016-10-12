<?php

/*
 * This file is part of the GitCommandBundle package.
 *
 * (c) Paul Schweppe <paulschweppe@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VersionControl\GitCommandBundle\GitCommands\Command;

use VersionControl\GitCommandBundle\GitCommands\GitDiffParser;
use VersionControl\GitCommandBundle\Entity\GitCommitFile;
use VersionControl\GitCommandBundle\Entity\Collections\GitCommitFileCollection;

/**
 * Description of GitFilesCommand
 *
 * @author Paul Schweppe <paulschweppe@gmail.com>
 */
class GitDiffCommand extends AbstractGitCommand {

    
    /**
     * Get diff based on Commit hash id
     * @return array()
     */
    public function getCommitDiff($commitHash){
         $diffString = $this->command->runCommand("git --no-pager show  --oneline ".escapeshellarg($commitHash));
         $diffParser = new GitDiffParser($diffString);
         $diffs = $diffParser->parse(); 
         return $diffs;
    }
    
    /**
     * Get diff on a file
     * @return array()
     */
    public function getDiffFile($filename){
         $diffString = $this->command->runCommand("git --no-pager diff  --oneline ".escapeshellarg($filename)."");
         $diffParser = new GitDiffParser($diffString);
         $diffs = $diffParser->parse(); 
         return $diffs;
    }
    
     /**
     * Get diff on a file between commits
     * @return array()
     */
    public function getDiffFileBetweenCommits($filename,$previousCommitHash,$commitHash){
         $diffString = $this->command->runCommand("git --no-pager diff  --oneline ".escapeshellarg($previousCommitHash)." ".escapeshellarg($commitHash)." -- ".escapeshellarg($filename)."");
         $diffParser = new GitDiffParser($diffString);
         $diffs = $diffParser->parse(); 
         return $diffs;
    }
    
    /**
     * Returns a list of files effected by a commit.
     * 
     * @return array() Array of file paths
     */
    public function getFilesInCommit($commitHash){
         $response = $this->command->runCommand("git diff-tree --no-commit-id --name-status -r ".escapeshellarg($commitHash)."");
         $responseLines = $this->splitOnNewLine($response);
         $files = new GitCommitFileCollection();
         foreach($responseLines as $line){
             $files->addGitCommitFile((new GitCommitFile($line)));
         }
         return $files;
    }
    
    public function getPreviousCommitHash($commitHash = 'HEAD',$fileName = false){
        $previousCommitHash = '';
        $command = " git log --pretty=format:'%h' -n 2 ".escapeshellarg($commitHash)."";
        if($fileName !== false){
            $command .= " ".escapeshellarg($fileName);
        }
        $response = $this->command->runCommand($command);
        $responseLines = $this->splitOnNewLine($response);
        if(count($responseLines) == 2){
            $previousCommitHash = trim($responseLines['1']);
        }
        
        return $previousCommitHash;
        
    }
    
    public function getAllPreviousCommitHash($commitHash = 'HEAD',$fileName = false){
        $previousCommitHash = '';
        $command = " git log --pretty=format:'%h' -n 60 ".escapeshellarg($commitHash)."";
        if($fileName !== false){
            $command .= " ".escapeshellarg($fileName);
        }
        $response = $this->command->runCommand($command);
        $responseLines = $this->splitOnNewLine($response);
        if(count($responseLines) == 2){
            $previousCommitHash = trim($responseLines['1']);
        }
        
        return $previousCommitHash;
        
    }
    
    
    
}