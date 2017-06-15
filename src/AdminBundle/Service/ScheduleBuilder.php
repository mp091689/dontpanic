<?php

namespace AdminBundle\Service;


use DateTime;
use DateTimeZone;
use WebBundle\Entity\Room;

class ScheduleBuilder
{
    private $room;

    private $timeZone;

    public function __construct(Room $room)
    {
        $this->room = $room;
        $this->timeZone = new DateTimeZone($room->getTimezone());
    }

    public function getSchedule()
    {
        $games = [];
        for (
            $date = new DateTime('now', $this->timeZone);
            $date < new DateTime('+13 days', $this->timeZone);
            $date->modify('+1 days')
        ) {
            $dayOfWeek = strtolower($date->format('l'));
            $blanks = $this->room->getBlanks();
            foreach ($blanks as $blank) {
                $dateTime = new DateTime($date->format('d-m-Y') . ' ' . $blank->getTime()->format('H:i'));
                $prices = $blank->getPricesByDayOfWeek($dayOfWeek);
                $games[] = [
                    'dateTime' => $dateTime,
                    'minPrice' => $this->getMinPrice($prices),
                    'prices' => $prices,
                    'busy' => $this->isExpired($dateTime)
                ];
            }
        }
        return $games;
    }

    public function collectByDate()
    {
        $dates = [];
        for (
            $date = new DateTime('now', $this->timeZone);
            $date < new DateTime('+13 days', $this->timeZone);
            $date->modify('+1 days')
        ) {
            $dayOfWeek = strtolower($date->format('l'));
            $blanks = $this->room->getBlanks();
            $games = [];
            foreach ($blanks as $blank) {
                $dateTime = new DateTime($date->format('d-m-Y') . ' ' . $blank->getTime()->format('H:i'));
                $prices = $blank->getPricesByDayOfWeek($dayOfWeek);
                $games[] = [
                    'time' => $blank->getTime()->format('H:i'),
                    'minPrice' => $this->getMinPrice($prices),
                    'prices' => $prices,
                    'busy' => $this->isExpired($dateTime)
                ];
            }
            $dates[] = [
                'date' => $date->format('d-m-Y'),
                'games' => $games
            ];
        }
        return $dates;
    }

    public function collectByTime()
    {
        $times = [];
        foreach ($this->room->getBlanks() as $blank) {
            $games = [];
            for (
                $date = new DateTime('now', $this->timeZone);
                $date < new DateTime('+13 days', $this->timeZone);
                $date->modify('+1 days')
            ) {
                $dayOfWeek = strtolower($date->format('l'));
                $dateTime = new DateTime($date->format('d-m-Y') . ' ' . $blank->getTime()->format('H:i'));
                $prices = $blank->getPricesByDayOfWeek($dayOfWeek);
                $games[] = [
                    'date' => $date->format('d-m-Y'),
                    'minPrice' => $this->getMinPrice($prices),
                    'prices' => $prices,
                    'busy' => $this->isExpired($dateTime)
                ];
            }
            $times[] = [
                'time' => $blank->getTime()->format('H:i'),
                'games' => $games
            ];
        }
        return $times;
    }

    private function isExpired(DateTime $date)
    {
        $now = new DateTime('now', $this->timeZone);
        return $now->format('d-m-Y H:i') > $date->format('d-m-Y H:i');
    }

    private function getMinPrice($prices)
    {
        $minPrice = 999999;
        if ($prices) {
            foreach ($prices as $price) {
                $minPrice = $price->getPrice() < $minPrice ? $price->getPrice() : $minPrice;
            }
            return $minPrice == 999999 ? null : $minPrice;
        }
        return null;
    }

}