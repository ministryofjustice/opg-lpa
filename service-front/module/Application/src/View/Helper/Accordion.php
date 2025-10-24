<?php

namespace Application\View\Helper;

use Application\Model\FormFlowChecker;
use MakeShared\DataModel\Lpa\Lpa;
use Laminas\Router\RouteMatch;
use Laminas\View\Helper\AbstractHelper;

class Accordion extends AbstractHelper
{
    private $lpa;

    /**
     * Full list of all the routes that can be used as bars in the accordion - in order
     */
    /** @var array */
    private $bars = [
        'lpa/form-type',
        'lpa/donor',
        'lpa/when-lpa-starts',
        'lpa/life-sustaining',
        'lpa/primary-attorney',
        'lpa/how-primary-attorneys-make-decision',
        'lpa/replacement-attorney',
        'lpa/when-replacement-attorney-step-in',
        'lpa/how-replacement-attorneys-make-decision',
        'lpa/certificate-provider',
        'lpa/people-to-notify',
        'lpa/instructions',
        'lpa/applicant',
        'lpa/correspondent',
        'lpa/who-are-you',
        'lpa/repeat-application',
        'lpa/fee-reduction',
    ];

    /** @var RouteMatch */
    private $routeMatch;

    /**
     * @param RouteMatch $routeMatch
     */
    public function __construct(RouteMatch $routeMatch)
    {
        $this->routeMatch = $routeMatch;
    }

    public function __invoke(?Lpa $lpa = null)
    {
        $this->lpa = $lpa;

        return $this;
    }

    /**
     * Return an array of route configs for bars that can appear above the page content for the current route
     *
     * @return array
     */
    public function top()
    {
        $barsInPlay = [];

        if ($this->lpa instanceof Lpa) {
            $flowChecker = new FormFlowChecker($this->lpa);
            $includeUpToRoute = $flowChecker->backToForm();

            // If the route for us to include up to is earlier than the current route...
            $currentRoute = $this->routeMatch->getMatchedRouteName();

            if (array_search($includeUpToRoute, $this->bars) < array_search($currentRoute, $this->bars)) {
                $includeUpToRoute = $currentRoute;
            }

            foreach ($this->bars as $route) {
                // Break at the route we are up to...
                if ($includeUpToRoute == $route) {
                    break;
                }

                // Break if we get to the current route...
                if ($currentRoute == $route) {
                    break;
                }

                // True iff the user is allowed to view this route name...
                if ($route == $flowChecker->getNearestAccessibleRoute($route)) {
                    $barsInPlay[] = ['routeName' => $route];
                }
            }

            // Added the special case bar for the review link.
            if ($this->lpa->isStateCreated()) {
                $barsInPlay[] = ['routeName' => 'review-link'];
            }
        }

        return $barsInPlay;
    }

    /**
     * Return an array of route configs for bars that can appear below the page content for the current route
     *
     * @return array
     */
    public function bottom()
    {
        $barsInPlay = [];

        if ($this->lpa instanceof Lpa) {
            $flowChecker = new FormFlowChecker($this->lpa);
            $includeUpToRoute = $flowChecker->backToForm();

            // Skip all routes before the current route...
            $currentRoute = $this->routeMatch->getMatchedRouteName();
            $startAt = array_search($currentRoute, $this->bars);
            $startAt = is_int($startAt) ? $startAt : 0;
            $bars = array_slice($this->bars, $startAt + 1); // +1 to start one past the page the current page.

            // For each possible page, starting from the user's current location...
            foreach ($bars as $key => $route) {
                // We only want to include 'this' bar if we can access 'this' page; AND
                // only if we can also access ANY other page after it.

                // Check we can access 'this' page...
                if ($route == $flowChecker->getNearestAccessibleRoute($route)) {
                    // Then check there are more pages...
                    if (isset($bars[intval($key) + 1])) {
                        // And that we can access at least one of them...
                        foreach (array_slice($bars, intval($key) + 1) as $futureRoute) {
                            // If we are able to access a future route, then this page is complete.
                            if ($futureRoute == $flowChecker->getNearestAccessibleRoute($futureRoute)) {
                                // All conditions met, so add the bar.
                                // We only need one page, so break when the first is found.
                                $barsInPlay[] = ['routeName' => $route];
                                break;
                            }
                        }
                    } elseif ($route == 'lpa/fee-reduction') {
                        // The last page is a special case as we cannot check past it.
                        // Therefore we have a custom check.
                        if ($this->lpa->payment instanceof \MakeShared\DataModel\Lpa\Payment\Payment) {
                            $barsInPlay[] = ['routeName' => $route];
                        }
                    }
                }

                // Give up here as we'd never show a bar past where the user has been.
                if ($includeUpToRoute == $route) {
                    break;
                }
            }
        }

        return $barsInPlay;
    }
}
