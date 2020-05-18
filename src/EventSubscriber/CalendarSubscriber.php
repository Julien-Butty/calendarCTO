<?php

namespace App\EventSubscriber;

use App\Repository\BookingRepository;
use CalendarBundle\CalendarEvents;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\CalendarEvent;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CalendarSubscriber implements EventSubscriberInterface
{
    /** @var BookingRepository */
    private $bookingRepository;
    /** @var UrlGeneratorInterface */
    private $router;

    public function __construct(
        BookingRepository $bookingRepository,
        UrlGeneratorInterface $router
    )
    {
        $this->bookingRepository = $bookingRepository;
        $this->router = $router;
    }

    public static function getSubscribedEvents()
    {
        return [
            CalendarEvents::SET_DATA => 'onCalendarSetData',
        ];
    }

    public function onCalendarSetData(CalendarEvent $calendar)
    {
        $start = $calendar->getStart();
        $end = $calendar->getEnd();
        $filter = $calendar->getFilters();

        $bookings = $this->bookingRepository
            ->createQueryBuilder('booking')
            ->where('booking.beginAt BETWEEN :start and :end OR booking.endAt BETWEEN :start and :end')
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->setParameter('end', $end->format('Y-m-d H:i:s'))
            ->getQuery()
            ->getResult()
        ;

        foreach ($bookings as $booking) {
            $bookingEvent = new Event(
                $booking->getTitle(),
                $booking->getBeginAt(),
                $booking->getEndAt()
            );


            $bookingEvent->setOptions([
                'backgroundColor' => 'red',
                'borderColor' => 'red',
            ]);

            $bookingEvent->addOption(
                'url',
                $this->router->generate('booking_show', [
                    'id' => $booking->getId(),
                    'hiddenDays'=> [1],
                ])
            );

            $calendar->addEvent($bookingEvent);
        }
    }
}
