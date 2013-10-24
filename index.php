<?php
/**************************************************************************************************\
*
* vim: ts=3 sw=3 et wrap co=100 go -=b
*
* Filename: "index.php"
*
* Project: Dictionary Fun.
*
* Purpose: The main file for the project.
*
* Author: Tom McDonnell 2010-02-22.
*
\**************************************************************************************************/

// Settings. ///////////////////////////////////////////////////////////////////////////////////////

error_reporting(-1);

// Includes. ///////////////////////////////////////////////////////////////////////////////////////

require_once dirname(__FILE__) . '/../../lib/tom/php/utils/UtilsValidator.php';
require_once dirname(__FILE__) . '/../../lib/tom/php/classes/AnagramFinder.php';

// Defines. ////////////////////////////////////////////////////////////////////////////////////////

define('MAX_SUBMITTED_STRING_LENGTH'           , 15); // Default 15.
define('MAX_STRLEN_TO_SOLVE_ANAGRAMS_DIRECTLY' ,  9); // Default  9.
define('MAX_STRLEN_TO_FIND_N_ANAGRAMS_DIRECTLY',  7); // Default  7.

// Global variables. ///////////////////////////////////////////////////////////////////////////////

$filesCss = array('style.css');

// Globally executed code. /////////////////////////////////////////////////////////////////////////

try
{
   list($submittedTextValidated, $includedWordsValidated) = validateGetString();

   // Default values for global variables.
   $errorMessage             = null;
   $n_anagramsByWordByLength = null;
   $anagrams                 = null;

   if (strlen($submittedTextValidated) > MAX_SUBMITTED_STRING_LENGTH)
   {
      $submittedTextValidated = null;
      $errorMessage           =
      (
         "The Anagram Finder will only find anagrams for strings of fifteen or fewer letters." .
         " Please enter a shorter string."
      );
   }

   if ($submittedTextValidated !== null)
   {
      $anagramFinder        = new AnagramFinder();
      $submittedTextReduced = $submittedTextValidated;

      if ($includedWordsValidated !== null)
      {
         foreach ($includedWordsValidated as $includedWord)
         {
            $submittedTextReduced = UtilsString::diff($submittedTextReduced, $includedWord);
         }
      }

      if (strlen($submittedTextReduced) > MAX_STRLEN_TO_SOLVE_ANAGRAMS_DIRECTLY)
      {
         $n_anagramsBySortedKSubsetByLength = getN_anagramsBySortedKSubsetsByLength
         (
            $submittedTextReduced
         );

         $n_anagramsByWordByLength =
         (
            getN_anagramsByWordByLengthFromN_anagramsBySortedKSubsetsByLength
            (
               $n_anagramsBySortedKSubsetByLength
            )
         );
      }
      else
      {
         $aTree          = $anagramFinder->getMultipleWordAnagramTree($submittedTextReduced);
         $anagramsAsKeys = $anagramFinder->getAnagramsAsKeysFromAnagramTreeRecursively($aTree); 
         $anagrams       = array_keys($anagramsAsKeys);
      }
   }
}
catch (Exception $e)
{
   echo $e;
}

// Functions. //////////////////////////////////////////////////////////////////////////////////////

/*
 *
 */
function validateGetString()
{
   UtilsValidator::checkArray
   (
      $_GET, array(), array
      (
         'submittedText' => 'string',
         'includedWords' => 'array'
      )
   );

   $submittedTextValidated = null;
   $includedWordsValidated = null;

   if (array_key_exists('submittedText', $_GET))
   {
      $submittedTextValidated = strtolower
      (
         UtilsString::removeAllNonAlphaCharacters($_GET['submittedText'])
      );
   }

   if (array_key_exists('includedWords', $_GET))
   {
      $includedWordsValidated = array();

      foreach ($_GET['includedWords'] as $includedWord)
      {
         $includedWordsValidated[] = UtilsString::removeAllNonAlphaCharacters($includedWord);
      }
   }

   return array($submittedTextValidated, $includedWordsValidated);
}

/*
 *
 */
function getN_anagramsBySortedKSubsetsByLength($submittedTextReduced)
{
   global $anagramFinder;

   $strlenSubmittedTextValidated = strlen($submittedTextReduced);

   $sortedKSubsetsByLength = $anagramFinder->getSortedKSubsetsWithAnagramsByLength
   (
      $submittedTextReduced
   );

   $n_anagramsBySortedKSubsetByLength = array();

   foreach ($sortedKSubsetsByLength as $length => $sortedKSubsets)
   {
      $n_anagramsBySortedKSubset = array();

      foreach ($sortedKSubsets as $sortedKSubset)
      {
         $strlenSortedKSubsetReduced = $strlenSubmittedTextValidated - strlen($sortedKSubset);

         if ($strlenSortedKSubsetReduced <= MAX_STRLEN_TO_FIND_N_ANAGRAMS_DIRECTLY)
         {
            $sortedKSubsetReduced = UtilsString::diff($submittedTextReduced, $sortedKSubset);

            $aTree          = $anagramFinder->getMultipleWordAnagramTree($sortedKSubsetReduced);
            $anagramsAsKeys = $anagramFinder->getAnagramsAsKeysFromAnagramTreeRecursively($aTree);
            $n_anagrams     = count($anagramsAsKeys);
         }
         else
         {
            $n_anagrams = null;
         }
         
         $n_anagramsBySortedKSubset[$sortedKSubset] = $n_anagrams;
      }

      $n_anagramsBySortedKSubsetByLength[$length] = $n_anagramsBySortedKSubset;
   }

   return $n_anagramsBySortedKSubsetByLength;
}

/*
 *
 */
function filterAnagrams($anagrams, $n = 0, $letter = 'd')
{
   $filteredAnagrams = array();

   foreach ($anagrams as $anagram)
   {
      $strlenAnagram = strlen($anagram);

      for ($i = 0; $i < $strlenAnagram; ++$i)
      {
         if ($anagram[$i] == $letter)
         {
            if ($i == $n)
            {
               $filteredAnagrams[] = $anagram;
               continue;
            }

            $spaceIndex = $i - $n - 1;

            if ($spaceIndex > 0 && $anagram[$spaceIndex] == ' ')
            {
               // Check for intervening spaces.
               for ($j = $n; $j < $i; ++$j)
               {
                  if ($anagram[$j] == ' ')
                  {
                     break (2);
                  }
               }

               $filteredAnagrams[] = $anagram;
            }
         }
      }
   }

   return $filteredAnagrams;
}

/*
 *
 */
function getN_anagramsByWordByLengthFromN_anagramsBySortedKSubsetsByLength
(
   $n_anagramsBySortedKSubsetByLength
)
{
   global $anagramFinder;

   $n_anagramsByWordByLength = array();

   foreach ($n_anagramsBySortedKSubsetByLength as $length => $n_anagramsBySortedKSubset)
   {
      $n_anagramsByWord = array();

      foreach ($n_anagramsBySortedKSubset as $sortedKSubset => $n_anagrams)
      {
         $words = $anagramFinder->getSingleWordAnagramsOfSortedAlphaString($sortedKSubset);

         foreach ($words as $word)
         {
            $n_anagramsByWord[$word] = $n_anagrams;
         }
      }

      ksort($n_anagramsByWord);

      $n_anagramsByWordByLength[$length] = $n_anagramsByWord;
   }

   return $n_anagramsByWordByLength;
}

/*
 *
 */
function echoWordsByLengthAsHtmlTableWithAnchors()
{
   global $anagramFinder;
   global $n_anagramsByWordByLength;

   $n_cols = count($n_anagramsByWordByLength);

   echo "  <pre>\n";
   echo "   <table>\n";
   echo "    <thead>\n";
   echo '     <tr>';

   foreach ($n_anagramsByWordByLength as $length => $words)
   {
      echo "<th>$length</th>";
   }

   echo     "</tr>\n";
   echo "    </thead>\n";
   echo "    <tbody>\n";

   $lastRowDisplayed = false;
   $n_blank          = 0;
   for ($rowNo = 0; !$lastRowDisplayed; ++$rowNo)
   {
      $lastRowDisplayed      = true;
      $n_anagramsByWordInRow = array();

      foreach ($n_anagramsByWordByLength as $length => $n_anagramsByWord)
      {
         $words = array_keys($n_anagramsByWord);
         $word  = (array_key_exists($rowNo, $words))? $words[$rowNo]: null;

         switch ($word === null)
         {
          case true:
            $n_anagramsByWordInRow['_' . (++$n_blank)] = null;
            break;
          case false:
            $n_anagramsByWordInRow[$word] = $n_anagramsByWord[$word];
            $lastRowDisplayed             = false;
         }
      }

      echo "     <tr>\n";

      foreach ($n_anagramsByWordInRow as $word => $n_anagrams)
      {
         echo "      <td>";

         if (substr($word, 0, 1) != '_')
         {
            $getString     = createGetString($word);
            $n_anagramsStr = ($n_anagrams === null)? '?': $n_anagrams;

            echo "<a href='index.php?$getString'>$word</a> ($n_anagramsStr)";
         }

         echo "</td>\n";
      }

      echo "     </tr>\n";
   }

   echo "    </tbody>\n";
   echo "   </table>\n";
   echo "  </pre>\n";
}

/*
 *
 */
function createGetString($newIncludedWord = null)
{
   global $submittedTextValidated;
   global $includedWordsValidated;

   if ($submittedTextValidated === null && $includedWordsValidated === null)
   {
      return '';
   }

   $getStringParams = array();

   if ($submittedTextValidated !== null)
   {
      $getStringParams[] = "submittedText=$submittedTextValidated";
   }

   if ($includedWordsValidated !== null)
   {
      foreach ($includedWordsValidated as $includedWord)
      {
         $getStringParams[] = "includedWords[]=$includedWord";
      }
   }

   if ($newIncludedWord !== null)
   {
      $getStringParams[] = "includedWords[]=$newIncludedWord";
   }

   return implode('&', $getStringParams);
}

/*
 *
 */
function echoAnagramsAsHtmlOrderedList()
{
   global $includedWordsValidated;
   global $anagrams;

   $includedWordsString =
   (
      ($includedWordsValidated === null)? '': implode(' ', $includedWordsValidated)
   );

   echo "  <ol>\n";

   foreach ($anagrams as $anagram)
   {
      echo "   <li>$includedWordsString $anagram</li>\n";
   }

   echo "  </ol>\n";
}

// HTML code. //////////////////////////////////////////////////////////////////////////////////////
?>
<!DOCTYPE html>
<html>
 <head>
<?php
 $unixTime = time();
 foreach ($filesCss as $file) {echo "  <link rel='stylesheet' href='$file?$unixTime'/>\n";}
?>
  <title>Anagram Finder</title>
 </head>
 <body>
  <a class='backLink' href='../../index.php'>Back to tomcdonnell.net</a> |
  <a class='backLink' href='../../submodules/anagram_checker/'>
   Anagram Checker
  </a>
  <h1>Anagram Finder</h1>
<?php
if ($errorMessage !== null)
{
   echo "  <p class='errorMessage'>$errorMessage</p>\n";
}

if ($submittedTextValidated !== null)
{
?>
  <a href='index.php'>[Back to start]</a>
  (Use the browser's back button to go back one step)
<?php
}

if ($n_anagramsByWordByLength !== null)
{
?>
  <p>
   The submitted string '<b><?php echo $submittedTextValidated; ?>'</b>
<?php
   if ($includedWordsValidated !== null)
   {
      echo "   minus (<b>'", implode("', '", $includedWordsValidated), "'</b>)\n";
   }
?>
   was too long for all the anagrams to be generated in one step.
  <p>
   Select a word from the following list to find all anagrams that include that word
<?php
   echo ($includedWordsValidated !== null)? 'also.': '.';
?>
  </p>
<?php
   echoWordsByLengthAsHtmlTableWithAnchors();
}

if ($submittedTextValidated === null && $includedWordsValidated === null)
{
?>
  <p>Type a word or short phrase in the box then click submit.</p>
  <form action='<?php echo $_SERVER['PHP_SELF']; ?>' method='GET'>
   <input type='text' name='submittedText' />
   <br /><br />
   <input type='submit' value='Submit' />
  </form>
<?php
}

if ($anagrams !== null)
{
   $n_anagrams = count($anagrams);

   if (count($includedWordsValidated) > 0)
   {
      echo "  <p>\n";
      echo "   $n_anagrams anagrams of '<b>$submittedTextValidated</b>' were found that";
      echo "   include the following word(s)\n";
      echo "  </p>\n";
      echo '  <ul><li>', implode('</li><li>', $includedWordsValidated), "</li></ul>\n";
   }
   else
   {
      echo "  <p>$n_anagrams anagrams of '<b>$submittedTextValidated</b>' were found.</p>\n";
   }

   if ($n_anagrams > 0)
   {
      echo "  <p>The anagrams found are listed below.</p>\n";
      echoAnagramsAsHtmlOrderedList();
   }
}
?>
 </body>
</html>
<?php
/*******************************************END*OF*FILE********************************************/
?>
