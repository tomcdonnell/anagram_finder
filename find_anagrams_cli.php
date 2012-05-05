<?php
/**************************************************************************************************\
*
* vim: ts=3 sw=3 et wrap co=100 go -=b
*
* Filename: "find_anagrams.php"
*
* Project: Dictionary Fun.
*
* Purpose: Command line interface for anagram finder.
*
* Author: Tom McDonnell 2010-03-07.
*
\**************************************************************************************************/

// Includes. ///////////////////////////////////////////////////////////////////////////////////////

require_once dirname(__FILE__) . '/lib_tom/php/classes/AnagramFinder.php';

// Globally executed code. /////////////////////////////////////////////////////////////////////////

try
{
   if (count($argv) != 2)
   {
      echo "Usage: php find_anagrams.php <word>\n";
      exit(0);
   }

   $submittedText = $argv[1];
   echo "Finding anagrams of '$submittedText'...\n";

   $anagramFinder  = new AnagramFinder();
   $anagramTree    = $anagramFinder->getMultipleWordAnagramTree($submittedText);
   $anagramsAsKeys = $anagramFinder->getAnagramsAsKeysFromAnagramTreeRecursively($anagramTree);

   if (count($anagramsAsKeys) == 0)
   {
      echo "No anagrams found.\n";
   }

   foreach (array_keys($anagramsAsKeys) as $anagram)
   {
      echo "$anagram\n";
   }
}
catch (Exception $e)
{
   echo $e->getMessage();
}

/*******************************************END*OF*FILE********************************************/
?>
