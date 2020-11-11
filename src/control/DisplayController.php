<?php

namespace SilverCommerce\OrdersAdmin\Control;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Assets\Image;
use SilverStripe\Security\Member;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Security\Security;
use SilverCommerce\OrdersAdmin\Model\Invoice;
use SilverCommerce\OrdersAdmin\Model\Estimate;
use SilverStripe\View\Requirements;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPStreamResponse;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use Dompdf\Dompdf;
use Dompdf\Options;

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
        "invoice",
        "invoicepdf",
        "estimate",
        "estimatepdf"
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
        $object = Estimate::get()
            ->byID($this->getrequest()->param("ID"));

        if ($object && (
            ($member && $object->canView($member)) ||
            ($object->AccessKey && $object->AccessKey == $this->request->param("OtherID"))
        )) {
            $this->object = $object;
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
    protected function gernerate_pdf($html)
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
     * Undocumented function
     *
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
    public function Link($action = "invoice")
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
    public function AbsoluteLink($action = "invoice")
    {
        return Controller::join_links(
            Director::absoluteBaseURL(),
            $this->Link($action)
        );
    }

    public function invoice(HTTPRequest $request)
    {
        $config = SiteConfig::current_site_config();
        
        $this->customise([
            "Type" => "Invoice",
            "HeaderContent" => $config->dbObject("InvoiceHeaderContent"),
            "FooterContent" => $config->dbObject("InvoiceFooterContent"),
            "Title" => _t("Orders.InvoiceTitle", "Invoice"),
            "MetaTitle" => _t("Orders.InvoiceTitle", "Invoice"),
            "Object" => $this->object
        ]);

        $this->extend("updateInvoice");
        
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
    public function invoicepdf(HTTPRequest $request)
    {
        /**
         * Load custom CSS for PDF explicitly (as pass)
         */
        $loader = ModuleResourceLoader::singleton();
        $style = file_get_contents($loader->resolvePath($this->config()->pdf_css));
        Requirements::clear();
        Requirements::customCSS(<<<CSS
        $style
CSS
        );
        $result = $this->invoice($request);
        $html = $result->getValue();
        $pdf = $this->gernerate_pdf($html);

        $this->extend("updateInvoicePDF", $pdf);

        $pdf->render();
        $pdf->stream("{$this->object->FullRef}.pdf");
        exit();
    }
    
    public function estimate(HTTPRequest $request)
    {
        $config = SiteConfig::current_site_config();
        $this->customise([
            "Type" => "Estimate",
            "HeaderContent" => $config->dbObject("EstimateHeaderContent"),
            "FooterContent" => $config->dbObject("EstimateFooterContent"),
            "Title" => _t("Orders.EstimateTitle", "Estimate"),
            "MetaTitle" => _t("Orders.EstimateTitle", "Estimate"),
            "Object" => $this->object
        ]);

        $this->extend("updateEstimate");
        
        return $this->render();
    }

    public function estimatepdf(HTTPRequest $request)
    {
        /**
         * Load custom CSS for PDF explicitly (as pass)
         */
        $loader = ModuleResourceLoader::singleton();
        $style = file_get_contents($loader->resolvePath('silvercommerce/orders-admin: client/dist/css/pdf.css'));
        Requirements::clear();
        Requirements::customCSS(<<<CSS
        $style
CSS
        );
        $result = $this->estimate($request);
        $html = $result->getValue();

        $pdf = $this->gernerate_pdf($html);

        $this->extend("updateEstimatePDF", $pdf);

        $pdf->render();
        $pdf->stream("{$this->object->FullRef}.pdf");
        exit();
    }
}
