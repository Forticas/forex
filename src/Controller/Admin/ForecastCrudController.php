<?php

namespace App\Controller\Admin;

use App\Entity\Forecast;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ForecastCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Forecast::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            DateTimeField::new('createdAt')->onlyOnIndex(),
            TextField::new('direction'),
            NumberField::new('entryPrice')->setNumDecimals(4),
            NumberField::new('takeProfit')->setNumDecimals(4),
            NumberField::new('stopLoss')->setNumDecimals(4),
        ];
    }

}
