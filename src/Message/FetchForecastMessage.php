<?php

namespace App\Message;

use App\Entity\ForecastLink;

final class FetchForecastMessage
{
   public function __construct(
       private ForecastLink $forecastLink
   )
   {
   }

    public function getForecastLink(): ForecastLink
    {
         return $this->forecastLink;
    }
}
