<?php


namespace JD_Tools;


class ViewBuilder
{
	public $data = [];

	private $tab = array();
	protected $active_tab;
	private $tabs = array();
	private $registry;

	public $tab_content = '';

	public function __construct($registry, &$data = []) {
		$this->registry = $registry;
		$this->data = $data;

		$this->language->load('tool/jd_tools');

		if (!empty($data['route'])) {
			$this->load->language($data['route']);
		}
		if(!empty($data['active_tab'])) {
			$this->active_tab = $data['active_tab'];
		}
		$this->getEnvironment();
	}

	public function __get($key) {
		return $this->registry->get($key);
	}

	private function getEnvironment(){
		$this->document->addStyle( 'view/stylesheet/jd_tools/main.css');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);
		// jd todo Зробити хлібні крихти автоматичними! Інлайн хардкод!
		$this->data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('tool/softko_1c_sync', 'user_token=' . $this->session->data['user_token'], true)
		);

		$this->data['heading_title'] = $this->language->get('heading_title');
	}

	public function addTab($id, $name) {
		if ($this->data['active_tab'] != $id ) {
			$this->tabs[$id] = [
				'id'    =>  $id,
				'name'  =>  $name,
				'actions' => [],
				'col_content'   =>  '<a href="' . $this->createLink('', $id ) . '">Завантажити контент вкладки</a>',
				'col_1'     =>  '',
				'col_2'     =>  '',
			];
		}
		else {
			$this->tabs[$id] = [
				'id'    =>  $id,
				'name'  =>  $name,
				'actions' => $this->tab['actions'],
				'col_content'   =>  '',
				'col_1'     =>  '',
				'col_2'     =>  '',
			];
		}


	}
	public function getTabs() {
		foreach ($this->tabs as &$tab) {
			$tab['content'] = $this->load->view('tool/jd_tools/snippets/tab', $tab);
		}
		return $this->tabs;
	}

	public function createLink($method = '', $active_tab = null, $params = array()) {
		if ($params !== array() && $params !== null) {
			$url = '';
			foreach ($params as $key => $value) {
				$url .= "&" . $key . "=" . $value;
			}
		} else $url = '';
		if (is_null($active_tab)) $url .= '&active_tab=' . $this->data['active_tab'];
		elseif ('' !== $active_tab) $url = '&active_tab=' . $active_tab . $url;

		$route = ($method)? $this->data['route'] . '/' . $method : $this->data['route'];

		$link = $this->url->link( $route, 'user_token=' . $this->session->data['user_token'] . $url, true);
		return $link;
	}
	/**
	 * Додавання лінку із кнопкою на активний таб
	 *
	 * Викликається всередині функції таба Tab_XXX(),
	 * додає кнопку із лінком в нього.
	 * $method - назва функції класу імпорт, параметри не передаються
	 *
	 * @param $name
	 * @param $method
	 * @param string $btn_type
	 * @param bool $disabled
	 */
	public function addAction($name, $method = '', $params = array(), $btn_type = 'primary', $disabled = false) {

		if (!$disabled) {
			$action_url = $this->createLink($method, $this->data['active_tab'], $params);
		}
		$data = array(
			'type' => $disabled? 'default' : (is_null($btn_type)? 'primary' : $btn_type) ,
			'btn_text' => $name,
			'action_url' => isset($action_url)? $action_url : '',
			'disabled' => $disabled,
		);
		$this->tab['actions'][] = $this->load->view('tool/jd_tools/snippets/button', $data);
	}
	public function addActionSeparator() {
		$this->tab['actions'][] = "<hr>";
	}
	public function getAction() {
		if (isset($this->request->get['action'])) {
			$action = $this->request->get['action'];
			switch ($action) {

			}
		}
	}

	/*
	 * Створення форми налаштувань
	 */
	//=============================================
	public function createSettingForm($data) {
		// todo jd params
		$params = [
			'inputs'    => [
				0   =>  'view1',
				1   =>  'view2',
				// ...
			],
		];
//		print_r($data);
		$data['name'] = empty($data['name'])? false : $data['name'];

		//todo jd view
//		$this->addAction('Зберегти', '', ['action' => 'saveSettings']);
		$view = $this->load->view('tool/jd_tools/snippets/setting_form', $data);
		$this->addMessage($view, 'input', 'div', 'content');
	}
	public function createInputField($data) {
		// params
		$params = [
			'label' =>  [
				'id'    =>  '',
				'text'  =>  '',
			],
			'type'  =>  '',
			'placeholder'   =>  '',
			'value' =>  '',
		];
		if(!empty($data['help']) && empty($data['help']['id'])) $data['help']['id'] = $data['id'] . '_help';
		$this->load->model('setting/setting');
		$value = $this->model_setting_setting->getSettingValue($this->module_setting_code . '_' . $data['id']);
		$data['value'] = empty($value)? false : $value;

		// view
		$view = $this->load->view('tool/jd_tools/snippets/input_field', $data);
		return $view;
	}

	/**
	 * Додає повідомлення на активний таб
	 *
	 * Викликається всередині функції таба TsbXXX(),
	 * По-замовчуванню додає повідомлення із текстом і тегом "р" в першу колонку таба ($->tab['col_1'])
	 * якщо $message - масив, то він буде перетворений через print_r($message, 1) і тег замінений на <pre></pre>.
	 *
	 * Колонка задається цифрою, 1 або 2
	 *
	 * @param $message
	 * @param $class        - html tag attribute class
	 * @param string $tag   - html tag
	 * @param int $col_num
	 */
	public function addMessage($message, $class = null, $tag = 'p', $col_num = 1, $message_on = false) {

		if((defined('MESSAGE_ON') && MESSAGE_ON) || $message_on) {
			if (is_array($message) || is_object($message)) {
				$message = print_r($message, 1);
				if (null === $tag || 'p' == $tag) $tag = 'pre';
			}
			if (null === $tag) $tag = 'p';
			if ($class) $class = " class='" . $class . "'";


			$this->tabs[$this->active_tab]['col_' . $col_num] = empty($this->tabs[$this->active_tab]['col_' . $col_num])?
				"<" . $tag . $class . ">" . $message . "</$tag>"
				: $this->tabs[$this->active_tab]['col_' . $col_num] . "<" . $tag . $class . ">" . $message . "</$tag>";
		}
	}
	public function addSMessage($message, $type = 'other_msg') {
		if (!is_string($message)) {
			ob_start();
			var_dump($message);
			$message = ob_get_clean();
		}
		if (isset($this->session->data[$type]))
			$this->session->data[$type] = '<p>' . $message . '</p>' . $this->session->data[$type];
		else $this->session->data[$type] = '<p>' . $message . '</p>';
	}
	public function getSMessages(){
		if (isset($this->session->data['error'])) {
			$this->data['error_warning'] = $this->session->data['error'];

			unset($this->session->data['error']);
		} else {
			$this->data['error_warning'] = '';
		}
		if (isset($this->session->data['success'])) {
			$this->data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$this->data['success'] = '';
		}
		if (isset($this->session->data['other_msg'])) {
			$this->data['other_msg'] = $this->session->data['other_msg'];
			unset($this->session->data['other_msg']);
		} else {
			$this->data['other_msg'] = '';
		}
	}

	public function addProgressBar($data) {
		$data['start_value'] = isset($data['start_value'])? $data['start_value'] : 0;
		$data['min_value'] = isset($data['min_value'])? $data['min_value'] : 0;
		$view = $this->load->view('tool/jd_tools/snippets/progressbar', $data);
		$this->addMessage($view, 'process', 'div', 'content');
	}

	public function render() {
		$this->data['tabs'] = $this->getTabs();
		$this->getSMessages();

		$this->data['header'] = $this->load->controller('common/header');
		$this->data['column_left'] = $this->load->controller('common/column_left');
		$this->data['footer'] = $this->load->controller('common/footer');

		return $this->response->setOutput($this->load->view('tool/jd_tools_main', $this->data));
	}
}