<?php

class wpjellyImportStateCheck {

    private $serviceStartTime = 0;

    private $checkStartTime = 0;

    private $averageTotalTime = 0;
    private $averageItemsProcessed = 0;
    private $averageSingleTime = 0;

    private $maxSingleTime = 0;

    public function recordStartTime() {
        $this->serviceStartTime = microtime( true );
    }

    public function currentImportTime() {
        return microtime( true ) - $this->serviceStartTime;
    }

    public function requestMaxTotalTime() {
        $default_max_time = 30;

        $process_max_time = @ini_get( 'max_execution_time' );

        if ( $process_max_time ) {
            return min( $process_max_time, $default_max_time );
        }

        return $default_max_time;
    }

    public function getTimeLeft() {
        $timeLeft = 0;

        if ( $this->requestMaxTotalTime() > $this->currentImportTime() ) {
            $timeLeft = $this->requestMaxTotalTime() - $this->currentImportTime();
        }

        return $timeLeft;
    }

    public function getSafeTimeLeft() {
        $safeTimeLeft = 0;
        $timeToFinish = 2;

        if ( $this->requestMaxTotalTime() > $this->currentImportTime() ) {
            if ( ( $this->requestMaxTotalTime() - $this->currentImportTime() ) > $timeToFinish ) {
                $safeTimeLeft = $this->requestMaxTotalTime() - $this->currentImportTime() - $timeToFinish;
            }
        }

        return $safeTimeLeft;
    }

    public function timeRecordStart () {
        $this->checkStartTime = microtime( true );
    }

    public function timeRecordEnd () {
        $diffTime = microtime( true ) - $this->checkStartTime;

        if ( $diffTime > $this->maxSingleTime ) {
            $this->maxSingleTime = $diffTime;
        }

        $this->averageTotalTime += $diffTime;
        $this->averageItemsProcessed++;

        $this->averageSingleTime = $this->averageTotalTime / $this->averageItemsProcessed;
    }

    public function averageImportTime() {
        return $this->averageSingleTime;
    }

    public function maxImportTime() {
        return $this->maxSingleTime;
    }
}
