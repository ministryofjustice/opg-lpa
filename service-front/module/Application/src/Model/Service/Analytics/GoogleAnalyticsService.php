<?php declare(strict_types=1);

namespace Application\Model\Service\Analytics;

use Application\Model\Service\AbstractService;
use Exception;
use Opg\Lpa\Logger\LoggerTrait;
use TheIconic\Tracking\GoogleAnalytics\Analytics;

class GoogleAnalyticsService extends AbstractService
{
    use LoggerTrait;

    /**
     * @var Analytics
     */
    private $analyticsClient;

    /**
     * Call Google Analytics to register a page view for the PDFs
     *
     * @param string $hostName The domain name of the environment e.g. example.domain.gov
     * @param string $pagePath Page url, excluding the domain e.g. /general/mypage
     * @param string $pageTitle Title of the page
     * @throws Exception
     */
    public function sendPageView(string $hostName, string $pagePath, string $pageTitle) : void
    {
        $this->getLogger()->debug('Sending analytics page view ' . $pagePath . ' , ' . $pageTitle);

        $clientId = $this->getAnalyticsClientId();

        $this->getLogger()->debug('Client ID: ' . $clientId);

        $analytics = $this->analyticsClient;

        $analytics->setProtocolVersion('1')
            ->setTrackingId('UA-33184303-1')
            ->setClientId($clientId)
            ->setDocumentHostName($hostName)
            ->setDocumentPath($pagePath)
            ->setDocumentTitle($pageTitle)
            ->setAnonymizeIp(true);

        $response = $analytics->sendPageview();

        $this->getLogger()->debug('Analytics response code: ' . $response->getHttpStatusCode());
    }

    /**
     * Get the client ID from the cookie
     *
     * @return string Client Id
     * @throws Exception
     */
    public function getAnalyticsClientId() : string
    {
        if (isset($_COOKIE['_ga'])) {
            list($version, $domainDepth, $cid1, $cid2) = explode('.', $_COOKIE['_ga'], 4);
            $contents = array('version' => $version, 'domainDepth' => $domainDepth, 'cid' => $cid1 . '.' . $cid2);
            $cid = $contents['cid'];
        } else {
            $this->getLogger()->notice('Could not find google analytics cookie - user likely has tracking blocked in their browser');
        }

        return $cid;
    }

    /**
     * Set the analytics client
     *
     * @param Analytics $analyticsClient Google analytics client
     */
    public function setAnalyticsClient(Analytics $analyticsClient) : void
    {
        $this->analyticsClient = $analyticsClient;
    }
}
