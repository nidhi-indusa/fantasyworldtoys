<?php
namespace Magesales\Knet\Block;
use Magento\Framework\Data\FormFactory;
class Redirect extends \Magento\Framework\View\Element\AbstractBlock
{
	protected $knetpayment;
	protected $registry;
	protected $formFactory;
	
	public function __construct(
        \Magento\Framework\View\Element\Context $context,
		\Magento\Framework\Registry $registry,
        \Magesales\Knet\Model\Payment $knetpayment,
		FormFactory $formFactory,
		array $data = []		
	)
	{
		parent::__construct($context,$data);
        $this->registry = $registry;
		$this->knetpayment = $knetpayment;
		$this->formFactory = $formFactory;		
    }
	
	protected $_methodCode = 'knet';

	public function getMethodCode()
    {
        return $this->_methodCode;
    }
	
	protected function _toHtml()
	{
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$standard = $objectManager->create('\Magesales\Knet\Model\Payment');
        $form = $this->formFactory->create();
        $form->setAction($standard->getKnetUrl())
            ->setId('knetform')
            ->setName('knetform')
            ->setMethod('POST')
            ->setUseContainer(true);
        foreach ($standard->setOrder($this->getOrder())->getStandardCheckoutFormFields() as $field => $value)
		{
            $form->addField($field, 'hidden', array('name' => $field, 'value' => $value));
        }

        $formHTML = $form->toHtml();

        $html = '<html><body>';
        //$html.= $this->__('You will be redirected to Alipay in a few seconds.');
        $html.= $formHTML;
        $html.= '<script type="text/javascript">document.getElementById("knetform").submit();</script>';
        $html.= '</body></html>';

		return $html;
    }
}