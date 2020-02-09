<?php
class wpjellyTemplatePaginationLinks{

  public static function create(
      $page,
      $numberOfPages,
      $context    = 1,
      $linkFormat = '<a href="?page=%d">%d</a>',
      $pageFormat = '<li class="_2OfdbQ7rBju3q4Oqif1u33_wpjelly current-page" data-current="1"><a role="button" tabindex="0" aria-label="Page 1 is your current page" aria-current="page">1</a></li>',
      $ellipsis   = '&hellip;'){

    // create the list of ranges
    $ranges = array(array(1, 1 + $context));
    self::mergeRanges($ranges, $page   - $context, $page + $context);
    self::mergeRanges($ranges, $numberOfPages - $context, $numberOfPages);

    // initialise the list of links
    $links = array();

    // loop over the ranges
    foreach ($ranges as $range){

      // if there are preceeding links, append the ellipsis
      if (count($links) > 0) $links[] = $ellipsis;

      // merge in the new links
      $links =
          array_merge(
              $links,
              self::createLinks($range, $page, $linkFormat, $pageFormat));

    }

    // return the links
    return implode(' ' , $links);

  }
  private static function mergeRanges(&$ranges, $start, $end){

    // determine the end of the previous range
    $endOfPreviousRange =& $ranges[count($ranges) - 1][1];

    // extend the previous range or add a new range as necessary
    if ($start <= $endOfPreviousRange + 1){
      $endOfPreviousRange = $end;
    }else{
      $ranges[] = array($start, $end);
    }

  }

  private static function createLinks($range, $page, $linkFormat, $pageFormat){

    // initialise the list of links
    $links = array();

    // loop over the pages, adding their links to the list of links
    for ($index = $range[0]; $index <= $range[1]; $index ++){
      // $links[] =
      //     sprintf(
      //         ($index == $page ? $pageFormat : $linkFormat),
      //         $index,
      //         $index);
    	if($index==$page)
    	{
    		$temp='<li class="_2OfdbQ7rBju3q4Oqif1u33_wpjelly current-page" data-current="'.$page.'"><a role="button" tabindex="0" aria-label="Page '.$page.' is your current page" aria-current="page">'.$page.'</a></li>';
    	}
    	else
    	{
    		$temp='<li><a role="button" tabindex="0" aria-label="Page '.$index.'" data-page="'.$index.'">'.$index.'</a></li>';
    	}
    	$links[] =$temp;
    }

    // return the array of links
    return $links;

  }

}