<?php

namespace Xxii\FormBundle\Document\Areabrick;

use Pimcore\Extension\Document\Areabrick\AbstractTemplateAreabrick;
use Pimcore\Model\Document\Editable\Area\Info;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use \Pimcore\Model\DataObject;


class Xxiicontact extends AbstractTemplateAreabrick
{
	public function getName(): string
	{
		return 'Formular';
	}

	public function getTemplate(): ?string
	{
		return '@XxiiForm/areas/xxiicontact/view.html.twig';
	}

	public function action(Info $info): ?Response
	{
		$formId = $this->getDocumentEditable($info->getDocument(), 'relation', 'formId')->getElement();

		$info->setParam('formId', DataObject\XxiiForm::getByPath($formId));

		if (!$formId) {
			$formId = 0;
		}

		$form = $this->buildForm($info->getRequest(), $formId);

		if ($form['formData']['success'] && $form['formData']['thxPage']) {
			return new RedirectResponse($form['formData']['thxPage']);
		}

		$info->setParam('error', '');
		$info->setParam('formTitle', '');
		$info->setParam('formCLass', '');
		$info->setParam('errors', $form['form']->getErrors());

		$info->setParam('formData', $form['formData']);
		$info->setParam('form', $form['form']->createView());

		return null;
	}

	protected function buildForm($request, $formId)
	{

		$session = $request->getSession();
		$randStrng = substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(6 / strlen($x)))), 1, 6);

		$formData = ['success' => false];
		$error = '';

		if (!$formId) {
			return 'no_id';
		}

		$formObject = DataObject\Form::getByPath($formId);

		if (!$formObject) {
			return 'no_object for id:' . $formId;
		}

		$formEntryFolder = $formObject->getFormData() ? $formObject->getFormData()->getId() : $formObject->getId();
		$formEmailTmpl = $formObject->getEmailTmplt() ? $formObject->getEmailTmplt() : '/shared/form/default';
		$thxPage = $formObject->getThxPage() ? $formObject->getThxPage() : '/';

		$formFactory = Forms::createFormFactoryBuilder()->addExtension(new HttpFoundationExtension())->getFormFactory();

		$formBuild = $formFactory->createBuilder();

		$formBuild->add('subject', HiddenType::class, [
			'label' => false,
			'required' => false,
		]);

		$formBuild->add('formId', HiddenType::class, [
			'data' => $formId,
		]);

		if ($formObject->getFields()) {
			$fields = $formObject->getFields()->getItems();

			foreach ($fields as $field) {

				$type = $field->getInputType();
				$label = $field->getLabel();
				$placeholder = $field->getPlaceholder();
				$name = \Pimcore\Model\Element\Service::getValidKey(urlencode($label), 'asset'); //$formObject->getId() . $field->getIndex();
				$required = ($field->getRequired() == null) ? false : $field->getRequired();
				$class = $field->getFieldClass();

				if ($type == 'text') {
					$formBuild->add($name, TextType::class, [
						'label' => $label,
						'attr' => [
							'placeholder' => $placeholder
						],
						'required' => $required,
						'row_attr' => [
							'class' => $class
						]
					]);
				} elseif ($type == 'email') {
					$formBuild->add($name, EmailType::class, [
						'label' => $label,
						'attr' => [
							'placeholder' => $placeholder
						],
						'required' => $required,
						'row_attr' => [
							'class' => $class
						]
					]);
				} elseif ($type == 'date') {
					$formBuild->add($name, DateType::class, [
						'label' => $label,
						'attr' => [
							'placeholder' => $placeholder,
							'type' => 'date'
						],
						'widget' => 'single_text',
						'html5' => true,
						'required' => $required,
						'row_attr' => [
							'class' => $class
						]
					]);
				} elseif ($type == 'time') {
					$formBuild->add($name, TimeType::class, [
						'label' => $label,
						'attr' => [
							'placeholder' => $placeholder,
							'type' => 'time'
						],
						'widget' => 'single_text',
						'input' => 'datetime',
						'required' => $required,
						'row_attr' => [
							'class' => $class
						]
					]);
				} elseif ($type == 'textarea') {

					$formBuild->add($name, TextareaType::class, [
						'label' => $label,
						'attr' => [
							'placeholder' => $placeholder
						],
						'required' => $required,
						'row_attr' => [
							'class' => $class
						]

					]);
				} elseif ($type == 'button') {
					$formBuild->add($name, SubmitType::class, [
						'label' => $label,
						'row_attr' => [
							'class' => $class,
							'button' => true
						]
					]);
				} elseif (in_array($type, ['choiceradio', 'choicecheckbox', 'select'])) {

					$choices = [];
					$multiple = ($type == 'choiceradio' || $type == 'select' ? false : true);
					$expandet = ($type == 'select' ? false : true);

					foreach ($field->getChoices() as $choice) {
						$choiceLabel = $choice['label']->getData();
						$choiceValue = $choice['choiceValue']->getData();
						$choices[$choiceLabel] = $choiceValue;
					}

					$formBuild->add($name, ChoiceType::class, [
						'label' => $label,
						'choices' => $choices,
						'expanded' => $expandet,
						'multiple' => $multiple,
						'required' => $required,
						'row_attr' => [
							'class' => $class
						]
					]);
				} elseif ($type == 'checkbox') {
					$formBuild->add($name, CheckboxType::class, [
						'label' => $label,
						'required' => $required,
						'row_attr' => [
							'class' => $class
						]
					]);
				} elseif ($type == 'headline') {
					$formBuild->add($name, FormType::class, [
						'label' => $label,
						'attr' => [
							'type' => 'headline'
						],
						'row_attr' => [
							'class' => $class
						]
					]);
				}

				$formBuild->add('captcha', TextType::class, [
					'label' => 'captcha',
					'attr' => [
						'type' => 'captcha'
					],
					'row_attr' => [
						'class' => 'captcha'
					]
				]);
			}
		}

		$form = $formBuild->getForm();
		$form->handleRequest($request);

		if ($form->isSubmitted()) {

			$formData = $form->getData();

			if ($session->get('capcha_code') == $formData['captcha']) {

				$emailData = ['data' => ''];

				/* save to object  */
				$newEntry = new DataObject\XxiiFormEntry();
				$newEntry->setKey(\Pimcore\Model\Element\Service::getValidKey(time() . '-entry', 'object'));
				$newEntry->setParentId($formEntryFolder);
				$newEntry->setRawData(json_encode($formData));

				$items = new DataObject\Fieldcollection();

				foreach ($formData as $key => $input) {

					$item = new DataObject\Fieldcollection\Data\EmailField();
					$item->setName($key);

					$fieldValue = '';

					if (is_array($input)) {
						foreach ($input as $checkbox) {
							$fieldValue .= ',' . $checkbox;
						}
					} else {
						$fieldValue = $input;
					}

					$item->setFieldValue($fieldValue);

					$items->add($item);

					/* email data */

					$emailData['data'] .= '<b>' . $key . '</b>: ' . $fieldValue . '<br />';

				}

				$newEntry->setFields($items);
				$newEntry->save();

				/* send email if configured  */

				$mail = new \Pimcore\Mail();
				$mail->setDocument($formEmailTmpl);
				$mail->setParams($emailData);

				if ($mail->send()) {
					$formData['thxPage'] = $thxPage;
					$formData['success'] = true;
				} else {
					$session->set('capcha_code', $randStrng);
					$formData['captchaView'] = $this->b64img($randStrng, 4, 213, 59);
					$formData['error']['captcha'] = 'Captcha ungülitg';
				}
			} else {
				$session->set('capcha_code', $randStrng);
				$formData['captchaView'] = $this->b64img($randStrng, 4, 213, 59);
				$formData['error']['captcha'] = 'Captcha ungülitg';
			}
		} else {
			$session->set('capcha_code', $randStrng);
			$formData['captchaView'] = $this->b64img($randStrng, 4, 213, 59);
		}

		return ['form' => $form, 'formData' => $formData];

//		return [
//			'form' => $form,
//			'formData' => $formData,
//			'formObject' => $formObject,
//			'error' => $error,
//			'formId' => $formId,
//		];
	}

	/**
	 * @param $str
	 * @param int $fs
	 * @param int $w
	 * @param int $h
	 * @param int[] $b
	 * @param int[] $t
	 * @return string
	 */
	function b64img($str, $fs = 1, $w = 100, $h = 59, $b = array('r' => 241, 'g' => 88, 'b' => 42), $t = array('r' => 22, 'g' => 27, 'b' => 36)): string
	{
		$tmp = tempnam(sys_get_temp_dir(), 'img');

		$image = imagecreate($w, $h);
		$imageLarge = imagecreate($w, $h);

		$bck = imagecolorallocate($image, $b['r'], $b['g'], $b['b']);
		$txt = imagecolorallocate($image, $t['r'], $t['g'], $t['b']);

		imagestring($image, $fs, $w / 8, $h / 8, $str, $txt);

		imagecopyresampled($imageLarge, $image, 0, 0, 0, 0, $w * 2, $h * 2, $w, $h);

		imagepng($imageLarge, $tmp);
		imagedestroy($imageLarge);

		$data = base64_encode(file_get_contents($tmp));
		@unlink($tmp);
		return $data;
	}

	public function getHtmlTagOpen(Info $info): string
	{
		return "";
	}

	public function getHtmlTagClose(Info $info): string
	{
		return "";
	}
}