<?php

namespace SilverCommerce\OrdersAdmin\Control;

use Dompdf\Dompdf;
use Dompdf\Options;
use SilverStripe\Core\Path;
use InvalidArgumentException;
use LeKoala\Uuid\UuidExtension;
use SilverStripe\Control\Director;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Control\HTTPStreamResponse;
use SilverCommerce\OrdersAdmin\Model\Invoice;
use SilverCommerce\OrdersAdmin\Model\Estimate;
use SilverStripe\Core\Manifest\ModuleResourceLoader;

/**
 * Controller responsible for displaying either an rendered order or a
 * rendered quote that can be emailed or printed.
 *
 * @package Orders
 */
class DisplayController extends Controller
{
    /**
     * ClassName of Order object
     *
     * @var string
     * @config
     */
    private static $url_segment = "ordersadmin/display";

    private static $allowed_actions = [
        "index",
        "pdf"
    ];

    /**
     * directory for pdf css file (in requirements format)
     *
     * @var string
     * @config
     */
    private static $pdf_css = 'silvercommerce/orders-admin: client/dist/css/pdf.css';

    /**
     * Ther object associated with this controller
     *
     * @var Estimate
     */
    protected $object;

    protected function init()
    {
        parent::init();

        $member = Security::getCurrentUser();
        $request = $this->getRequest();
        $id = $request->param("ID");
        $key = $request->param("OtherID");
        $is_uuid = true;
        $object = null;

        // If needed keys are not present, return 404
        if (empty($id) || empty($key)) {
            return $this->httpError(404);
        }

        // Is ID a UUID or a legacy ID format?
        try {
            UuidExtension::getUuidFormat($id);
        } catch (InvalidArgumentException $e) {
            $is_uuid = false;
        }

        // Either try and retrieve the object by the relevant
        // format
        if ($is_uuid) {
            $object = UuidExtension::getByUuid(Estimate::class, $id);
        } elseif ((int)$id > 0) {
            $object = Estimate::get_by_id($id);
        }

        // Ensure order is available and has an access key
        if (empty($object) || empty($object->AccessKey)) {
            return $this->httpError(403);
        }

        if ($object && (
            ($member && $object->canView($member)) ||
            ($object->AccessKey && $object->AccessKey === $key)
        )) {
            $this->setObject($object);
        } else {
            return Security::permissionFailure();
        }
    }

    /**
     * Generate a Dompdf object from the provided html
     *
     * @param string $html
     * @return Dompdf
     */
    protected function generate_pdf($html)
    {
        $options = new Options([
            "compressed" => true,
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true
        ]);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');

        return $dompdf;
    }

    /**
     * @return \SilverStripe\Assets\Image
     */
    public function Logo()
    {
        $config = SiteConfig::current_site_config();
        $image = $config->EstimateInvoiceLogo();

        $this->extend("updateLogo", $image);
        
        return $image;
    }

    /**
     * get the current logo as a base 64 encoded string
     *
     * @return string
     */
    public function LogoBase64(int $width = 0, int $height = 0)
    {
        $logo = $this->Logo();

        if ($width > 0 && $height > 0) {
            $logo = $logo->Fit($width, $height);
        }
        $string = base64_encode($logo->getString());

        $this->extend("updateLogoBase64", $string);
        
        return $string;
    }

    /**
     * Get a relative link to anorder or invoice
     *
     * NOTE: this controller will always require an ID of an order and
     * access key to be passed (as well as an action).
     *
     * @param $action Action we would like to view.
     * @param $id ID or the order we want to view.
     * @param $key Access key of the order (for security).
     * @return string
     */
    public function Link($action = "index")
    {
        return Controller::join_links(
            $this->config()->url_segment,
            $action
        );
    }

    /**
     * Get an absolute link to an order or invoice
     *
     * NOTE: this controller will always require an ID of an order and
     * access key to be passed (as well as an action).
     *
     * @param $action Action we would like to view.
     * @param $id ID or the order we want to view.
     * @param $key Access key of the order (for security).
     * @return string
     */
    public function AbsoluteLink($action = "index")
    {
        return Controller::join_links(
            Director::absoluteBaseURL(),
            $this->Link($action)
        );
    }

    /**
     * Generate the CSS for the rendered PDF as a string (based on the provided path in config)
     *
     * @return string
     */
    protected function getPdfCss()
    {
        $loader = ModuleResourceLoader::singleton();
        $path = $loader->resolvePath($this->config()->pdf_css);

        // If using a public dir, then apend it's location
        if (Director::publicDir() && defined('RESOURCES_DIR')) {
            // All resources mapped directly to _resources/
            $path = Path::join(RESOURCES_DIR, $path);
        }

        return file_get_contents($path);
    }

    public function index(HTTPRequest $request)
    {   
        return $this->render();
    }

    /**
     * Generate a PDF based on the invoice html output.
     *
     * @todo At the moment this exits all execution after generating
     * and streaming PDF. Ideally this should tap into
     * @link http://api.silverstripe.org/4/SilverStripe/Control/HTTPStreamResponse.html
     *
     * @param HTTPRequest $request
     * @return void
     */
    public function pdf(HTTPRequest $request)
    {
        $style = $this->getPdfCss();
        Requirements::clear();
        Requirements::customCSS(<<<CSS
        $style
CSS
        );

        /** @var DBHTMLText */
        $result = $this->index($request);
        $html = $result->getValue();
        $pdf = $this->generate_pdf($html);

        $this->extend("updateInvoicePDF", $pdf);

        $pdf->render();
        $pdf->stream("{$this->object->FullRef}.pdf");
        exit();
    }

    /**
     * Get the object associated with this controller
     *
     * @return Estimate
     */ 
    public function getObject(): Estimate
    {
        return $this->object;
    }

    /**
     * Set the object associated with this controller
     *
     * @param Estimate $object
     *
     * @return self
     */ 
    public function setObject(Estimate $object)
    {
        $this->object = $object;
        return $this;
    }
}
