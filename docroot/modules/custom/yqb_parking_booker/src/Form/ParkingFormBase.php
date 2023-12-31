<?php
/**
 * @file
 * Contains \Drupal\yqb_parking_booker\Form\ParkingBookerForm.
 */

namespace Drupal\yqb_parking_booker\Form;

use DateTime;
use DateTimeZone;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Url;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\advam\Connector\AdvamConnector;
use NumberFormatter;

class ParkingFormBase extends FormBase {

	/**
	 * @var \Drupal\user\PrivateTempStoreFactory
	 */
	protected $tempStoreFactory;

	/**
	 * @var \Drupal\Core\Session\SessionManagerInterface
	 */
	protected $sessionManager;

	/**
	 * @var \Drupal\Core\Session\AccountInterface
	 */
	protected $currentUser;

	/**
	 * @var \Drupal\user\PrivateTempStore
	 */
	protected $store;

	/**
	 * @var \Drupal\advam\Connector\AdvamConnector
	 */
	protected $advam = NULL;

	/**
	 * Constructs a \Drupal\demo\Form\ParkingBookerForm
	 *
	 * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
	 * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
	 * @param \Drupal\Core\Session\AccountInterface $current_user
	 */
	public function __construct(PrivateTempStoreFactory $temp_store_factory, SessionManagerInterface $session_manager, AccountInterface $current_user) {
		$this->advam = new AdvamConnector();

		$this->tempStoreFactory = $temp_store_factory;
		$this->sessionManager = $session_manager;
		$this->currentUser = $current_user;

    $this->store = $this->tempStoreFactory->get('multistep_data');

    //Start session if is user Anonymous
    if (!isset($_SESSION['session_started']) && $this->currentUser->isAnonymous() ) {
      $_SESSION['session_started'] = true;
      $this->sessionManager->start();
    }
	}

	/**
	 * {@inheritdoc}
	 */
	public static function create(ContainerInterface $container) {
		return new static(
				$container->get('user.private_tempstore'),
				$container->get('session_manager'),
				$container->get('current_user')
		);
	}

	public function getFormId() {
		// TODO: Implement getFormId() method.
	}

	/**
	 * {@inheritdoc}.
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {
		// Start a manual session for anonymous users. Moved when the form is submitted
		//        if ($this->currentUser && $this->currentUser->isAnonymous() && !isset($_SESSION['multistep_form_holds_session'])) {
		//            $_SESSION['multistep_form_holds_session'] = TRUE;
		//            $this->sessionManager->start();
		//        }

		$form = [];

		$form['#cache']['max_age'] = 0;

		$form['#attributes']['class'][] = 'form';
		$form['#attributes']['class'][] = 'parking-booker-form';

		$form['actions']['#type'] = 'actions';
		$form['actions']['#attributes'] = ['class' => ['text-right']];
		$form['actions']['submit'] = [
				'#attributes' => ['class' => ['btn', 'btn-default']],
				'#type' => 'submit',
				'#value' => $this->t('Submit'),
				'#button_type' => 'default',
				'#weight' => 10,
		];

		return $form;
	}

	public function validateForm(array &$form, FormStateInterface $form_state) {
		parent::validateForm($form, $form_state); // TODO: Change the autogenerated stub
	}

	public function submitForm(array &$form, FormStateInterface $form_state) {
		// TODO: Implement submitForm() method.
	}

	/**
	 * Saves the data from the multistep form.
	 */
	protected function saveData() {
		// Logic for saving data goes here...
		$this->deleteStore();
		drupal_set_message($this->t('The form has been saved.'));

	}

	/**
	 * Helper method that removes all the keys from the store collection used for
	 * the multistep form.
	 */
	protected function deleteStore($exceptions = []) {
		$exceptions = ($exceptions) ? $exceptions : [];

		$keys = [
				'product_id',
				'arrival_date',
				'departure_date',
				'promo_code',
				'current_booking',
				'booking',
				'initialization',
				'cancellation',
				'modification',
				'confirmation',
				'transaction',
				'processed_amount',
				'refund',
				'webview',
				'user_id',
				'user_flight',
				'reference_number',
				'terms',
				'receive_communication'
		];

		foreach ($keys as $key) {
			if (in_array($key, $exceptions) === false) {
				$this->store->delete($key);
			}
		}
	}

	protected function updateBookingReservation($modification) {
		$results = \Drupal::entityQuery('node')
				->condition('type', 'parking_booking')
				->condition('field_advam_reference', $this->store->get('current_booking')->reference)
				->pager(1)
				->execute();

		if (!empty($results)) {
			$booking = Node::load(current($results));

			$arrivalDate = new DateTime($modification->booking->arrivalDate . ' ' . $modification->booking->arrivalTime, new DateTimeZone("UTC"));
			$departureDate = new DateTime($modification->booking->departureDate . ' ' . $modification->booking->departureTime, new DateTimeZone("UTC"));

			$booking->setTitle('Parking / ' . $modification->booking->reference);
			$booking->set('field_arrival', $arrivalDate->format("Y-m-d\TH:i:s"));
			$booking->set('field_departure', $departureDate->format("Y-m-d\TH:i:s"));
			$booking->set('field_advam_guid', $modification->booking->guid);
			$booking->set('field_advam_reference', $modification->booking->reference);
			return $booking->save();
		} else {
			return false;
		}
	}

	protected function generateFooter() {
		return [
				'#type' => 'container',
				'#cache' => ['max-age' => 0],
				'#weight' => 100,
				'#attributes' => ['class' => ['parking-accepted-card']],
				'title' => [
						'#type' => 'html_tag',
						'#tag' => 'span',
						'#value' => $this->t("Cartes acceptées")
				],
				'list' => [
						'#theme' => 'item_list',
						'#items' => [
								[
										'#type' => 'html_tag',
										'#tag' => 'span',
										'#attributes' => ['class' => ['icon', 'icon-visa']],
								],
								[
										'#type' => 'html_tag',
										'#tag' => 'span',
										'#attributes' => ['class' => ['icon', 'icon-mastercard']],
								],
								[
										'#type' => 'html_tag',
										'#tag' => 'span',
										'#attributes' => ['class' => ['icon', 'icon-americanexpress']],
								]
						]
				]
		];

	}

	protected function generateHeader() {
		$header = [
				'#type' => 'container',
				'#cache' => ['max-age' => 0],
				'#weight' => 0,
		];

		// Steps / Breadcrumb

		$steps = [
				'intro' => [
						'label' => $this->t("Faire une réservation"),
						'link' => \Drupal\Core\Url::fromRoute('page_manager.page_view_parking_booking_panels')->toString()
				],
				'results|extras' => [
						'label' => $this->t("Choix du stationnement"),
						'link' => \Drupal\Core\Url::fromRoute(sprintf('yqb_parking_booker.%s.results', \Drupal::languageManager()->getCurrentLanguage()->getId()))->toString()
				],
				'payment|review' => [
						'label' => $this->t("Informations personnelles"),
						'link' => \Drupal\Core\Url::fromRoute(sprintf('yqb_parking_booker.%s.review', \Drupal::languageManager()->getCurrentLanguage()->getId()))->toString()
				],
				'confirmation' => [
						'label' => $this->t("Confirmation"),
						'link' => \Drupal\Core\Url::fromRoute(sprintf('yqb_parking_booker.%s.confirmation', \Drupal::languageManager()->getCurrentLanguage()->getId()))->toString()
				],
		];

		$breadcrumb = [
				'#type' => 'container',
				'#attributes' => [
						'class' => [
								'parking-booker-breadcrumb',
								'row'
						]
				]
		];

		$lastStep = false;
		$countStep = 0;

		// First, determine if we've reached last step
		foreach ($steps as $key => $step) {
			$countStep++;

			if (preg_match('/parking_booker_(' . $key . ')_form/', $this->getFormId())) {
				if ($countStep >= count($steps)) {
					$lastStep = true;
				}
			}
		}

		$passedActive = false;
		$countStep = 0;

		// Build steps array
		foreach ($steps as $key => $step) {
			$isActive = false;

			if (preg_match('/parking_booker_(' . $key . ')_form/', $this->getFormId())) {
				$isActive = true;
				$passedActive = true;
			}

			$countStep++;

			$breadcrumb[$key] = [
					'#type' => 'html_tag',
					'#tag' => (!$passedActive && !$lastStep) ? 'a' : 'div',
					'#attributes' => [
							'href' => (!$passedActive && !$lastStep) ? $step['link'] : null,
							'class' => [
									(!$passedActive) ? 'is-done ' : NULL,
									($isActive) ? 'is-active ' : NULL,
									'col-md-' . round(12 / count($steps)) . ' col-sm-12'
							]
					],
					'#value' => '<span>' . $step['label'] . '</span>' . ' <span class="step-mobile"> - ' . $countStep . $this->t(" de ") . count($steps) . '</span>'
			];
		}

		$header['breadcrumb'] = $breadcrumb;

		// Status

		$status = [
				'#type' => 'container',
				'#attributes' => [
						'class' => [
								'parking-booker-status',
								'row'
						]
				]
		];

		$params = ($this->store->get('webview') || isset($_GET['webview'])) ? ['webview' => 1] : [];

		$back = [
				'#attributes' => ['class' => ['btn-back']],
				'#type' => 'link',
				'#url' => \Drupal\Core\Url::fromRoute('page_manager.page_view_parking_booking_panels', $params),
				'#title' => $this->t('<span class="icon icon-left-arrow" data-grunticon-embed></span>')
		];

		$arrivalDate = [
				'#type' => 'container',
				'#attributes' => ['class' => [
						($this->store->get('promo_code')) ? 'col-md-3 col-sm-4 col-xs-6' : 'col-md-4 col-sm-6 col-xs-6',
						'parking-booker-status-wrapper',
				]],
				'arrival_date' => [
						'#type' => 'container',
						'#attributes' => ['class' => ['parking-status-info']],
						'label' => [
								'#type' => 'html_tag',
								'#tag' => 'span',
								'#value' => $this->t("Date d'arrivée"),
						],
						'value' => [
								'#type' => 'container',
								'#markup' => date('Y-m-d H:i', strtotime($this->store->get('arrival_date'))),
						],
				],
		];

		$departureDate = [
				'#type' => 'container',
				'#attributes' => ['class' => [
						($this->store->get('promo_code')) ? 'col-md-3 col-sm-4 col-xs-6' : 'col-md-4 col-sm-6 col-xs-6',
						'parking-booker-status-wrapper',
				]],
				'departure_date' => [
						'#type' => 'container',
						'#attributes' => ['class' => ['parking-status-info']],
						'label' => [
								'#type' => 'html_tag',
								'#tag' => 'span',
								'#value' => $this->t("Date de sortie"),
						],
						'value' => [
								'#type' => 'container',
								'#markup' => date('Y-m-d H:i', strtotime($this->store->get('departure_date'))),
						],
				],
		];

		$help = [
				'#type' => 'container',
				'#attributes' => ['class' => [
						'btn-help',
				]],
				'help' => [
						'#type' => 'link',
						'#url' => \Drupal\Core\Url::fromRoute('<front>'),
						'#title' => $this->t("Aide?"),
				],
		];

		$booking = null;
		$promoCode = null;
		$price = null;

		if ($this->store->get('promo_code')) {
			$promoCode = [
					'#type' => 'container',
					'#attributes' => ['class' => [
							'col-md-2 col-sm-4 col-xs-12',
							'parking-booker-status-wrapper',
					]],
					'promo_code' => [
							'#type' => 'container',
							'#attributes' => ['class' => ['parking-status-info']],
							'label' => [
									'#type' => 'html_tag',
									'#tag' => 'span',
									'#value' => $this->t("Code promotionnel"),
							],
							'value' => [
									'#type' => 'container',
									'#markup' => $this->store->get('promo_code'),
							],
					],
			];
		} //end code prome


		if ($this->store->get('booking')) {
			$booking = $this->advam->getBooking($this->store->get('booking')->guid);

			if ($booking) {
				$this->store->set('booking', $booking->booking);
			}
		}

		if ($booking) {
			$product = $this->advam->getProduct($this->store->get('product_id'));

			$parkingSlug = strtolower($product->code);

			switch ($parkingSlug) {
				case 'p1':
					$parkingSlug = 'inparq';
				break;
				case 'p2':
					$parkingSlug = 'proxiparq';
				break;
			}

			$price = [
					'#type' => 'container',
					'#attributes' => ['class' => [
							($this->store->get('promo_code')) ? 'col-md-4 col-xs-12' : 'col-md-4 col-xs-12',
							'parking-booker-status-wrapper',
							'parking-choice',
							'parking-choice-proxiparq',
					]],
					'product' => [
							'#type' => 'container',
							'#attributes' => ['class' => ['parking-choice-name', 'parking-booker-status-wrapper']],
							'container' => [
									'#type' => 'container',
									'#cache' => ['max-age' => 0],
									'#attributes' => ['class' => ['parking-status-info']],
									'#markup' => '<span class="icon icon-' . $parkingSlug . '" data-grunticon-embed></span>'
								/*
								'label' => [
										'#type' => 'html_tag',
										'#tag' => 'span',
										'#value' => $this->t("Proxiparc"),
								],
								*/
							],
					],
					'price' => [
							'#type' => 'container',
							'#attributes' => ['class' => ['parking-choice-price']],
							'container' => [
									'#type' => 'container',
									'#attributes' => ['class' => ['parking-status-info']],
									'label' => [
											'#type' => 'html_tag',
											'#tag' => 'span',
											'#value' => $this->t("Total"),
									],
									'value' => [
											'#type' => 'container',
											'#markup' => $this->moneyFormat($this->store->get('booking')->price),
									],
							],
					],
			];

			if ($this->store->get('current_booking')) {
				$price['price']['container']['label-2'] = [
						'#type' => 'html_tag',
						'#tag' => 'span',
						'#value' => $this->t("Changement"),
				];

				$price['price']['container']['value-2'] = [
						'#type' => 'container',
						'#markup' => $this->moneyFormat($this->store->get('booking')->price - $this->store->get('current_booking')->items->parkings[0]->amount),
				];
			}
		}

		$status[] = $back;
		$status[] = $arrivalDate;
		$status[] = $departureDate;
		$status[] = $price;
        $status[] = $promoCode;
		// $status[] = $help;

		$header['status'] = $status;

		return $header;
	}

	protected function generateStatusBar($details = [], $backUrl = 'page_manager.page_view_parking_booking_panels') {
		$status = [
				'#type' => 'container',
				'#attributes' => [
						'class' => [
								'parking-booker-status',
								'row'
						]
				]
		];

		$params = ($this->store->get('webview') || isset($_GET['webview'])) ? ['webview' => 1] : [];

		$back = [
				'#attributes' => ['class' => ['btn-back']],
				'#type' => 'link',
				'#url' => \Drupal\Core\Url::fromRoute($backUrl, $params),
				'#title' => $this->t('<span class="icon icon-left-arrow" data-grunticon-embed></span>')
		];

		$status['back'] = $back;

		$i = 0;
		foreach ($details as $label => $value) {
			$classes = ($i == 0) ? ['col-md-4 col-sm-12', 'parking-booker-status-wrapper', 'parking-booker-status-reference'] : ['col-md-4 col-sm-6 col-xs-6', 'parking-booker-status-wrapper'];

			$status[] = [
					'#type' => 'container',
					'#attributes' => ['class' => $classes],
					'departure_date' => [
							'#type' => 'container',
							'#attributes' => ['class' => ['parking-status-info']],
							'label' => [
									'#type' => 'html_tag',
									'#tag' => 'span',
									'#value' => $this->t($label),
							],
							'value' => [
									'#type' => 'container',
									'#markup' => $value,
							],
					],
			];

			$i++;
		}

		return $status;
	}

	protected function generateModal($id, $title = "", $body = []) {
		return [
				'#type' => 'container',
				'#attributes' => ['id' => $id, 'class' => ['modal fade'], 'tabindex' => '-1', 'role' => 'dialog'],
				'dialog' => [
						'#type' => 'container',
						'#attributes' => ['class' => ['modal-dialog']],
						'content' => [
								'#type' => 'container',
								'#attributes' => ['class' => ['modal-content']],
								'header' => [
										'#type' => 'container',
										'#attributes' => ['class' => ['modal-header']],
										'close' => [
												'#type' => 'html_tag',
												'#tag' => 'button',
												'#attributes' => ['type' => 'button', 'class' => ['close'], 'data-dismiss' => 'modal', 'aria-label' => 'Close'],
												'#value' => $this->t('<span aria-hidden="true">&times;</span>'),
										],
										'title' => [
												'#type' => 'html_tag',
												'#tag' => 'h4',
												'#attributes' => ['class' => ['modal-title']],
												'#value' => $title,
										]
								],
								'body' => array_merge([
										'#type' => 'container',
										'#attributes' => ['class' => ['modal-body']],
								],
										$body
								)
						]
				]
		];
	}

	protected function generateBookingSummary($booking) {
		$order = [
				'#type' => 'container',
				'#theme' => 'table',
				'#responsive' => false,
				'#cache' => ['max-age' => 0],
				'#attributes' => ['class' => ['table', 'table-normal', 'table-summary']],
				'#header' => [
						$this->t('Produit'),
						$this->t('Quantité'),
						$this->t('Prix'),
				],
				'#rows' => [],
				'#empty' => $this->t('Aucun résultat.')
		];

		foreach ($booking->items as $category => $items) {
			if (!count($items)) {
				continue;
			}

			foreach ($items as $item) {
				$info = $this->t($category);
				$price = $this->moneyFormat($item->amount);
				$quantity = 1;

				if ($item->productId) {
					$product = $this->advam->getProduct($item->productId);
					$info = $product->name;
					$quantity = 1;

					$order['#rows'][] = $this->generateRow($info, $quantity, $price);
				} else if ($item->extraId) {
					$extra = $this->advam->getExtra($item->extraId);
					$info = $extra->name;
					$quantity = 1;

					if ($item->offerLines) {
						foreach ($item->offerLines as $offerLine) {
							foreach ($booking->items->extras as $bookingExtra) {
								if ($bookingExtra->extraId === $item->extraId) {
									foreach ($bookingExtra->offerLines as $bookingExtraOfferLine) {
										if ($offerLine->extraOfferLineId === $bookingExtraOfferLine->extraOfferLineId) {
											$price = $bookingExtraOfferLine->amount;
											$quantity = $bookingExtraOfferLine->quantity;
											break;
										}
									}
								}
							}

							foreach ($extra->offerLines as $extraOfferLine) {
								if ($offerLine->extraOfferLineId === $extraOfferLine->id) {
									$info = strip_tags($extraOfferLine->description);
									break;
								}
							}

							$order['#rows'][] = $this->generateRow($info, $quantity, $price);
						}
					}
				} else {
					$order['#rows'][] = $this->generateRow($info, $quantity, $price);
				}
			}
		}

		$order['#rows'][] = [
				'class' => '',
				'data' => [
						[],
						[
								'class' => '',
								'data' => ['#markup' => '<strong>' . $this->t("Total") . '</strong>']
						],
						[
								'class' => '',
								'data' => ['#markup' => $this->moneyFormat($booking->price)]
						],
				],
		];

		return $order;
	}

	protected function generateBookingView($booking, $confirmation) {
		// Build product icon
		if ($booking->carParkId) {
			$product = $this->advam->getCarPark($booking->carParkId);
			$parkingSlug = strtolower($product->code);

			switch ($parkingSlug) {
				case 'p1':
					$parkingSlug = 'inparq';
				break;
				case 'p2':
					$parkingSlug = 'proxiparq';
				break;
			}

			$product = [
					'#type' => 'container',
					'#attributes' => ['class' => ['parking-name', 'parking-name-' . $parkingSlug]],
					'data' => ['#markup' => '<span class="icon icon-' . $parkingSlug . '" data-grunticon-embed></span>'],
			];
		} else {
			$product = [];
		}

		// Build dates
		$schedule = [
				'#type' => 'container',
				'#attributes' => ['class' => ['row-inner', 'row-cols-gutter']],
				'arrival' => [
						'#type' => 'container',
						'#attributes' => ['class' => ['col-xs-6', 'parking-schedule']],
						'data' => [
								'#markup' => "<span>Date d'entrée</span>" . date($this->t('Y-m-d<\b\r>H\hi'), strtotime($booking->arrivalDate . ' ' . $booking->arrivalTime))
						]
				],
				'departure' => [
						'#type' => 'container',
						'#attributes' => ['class' => ['col-xs-6', 'parking-schedule']],
						'data' => [
								'#markup' => "<span>Date de sortie</span>" . date($this->t('Y-m-d<\b\r>H\hi'), strtotime($booking->departureDate . ' ' . $booking->departureTime))
						]
				],
		];

		// Build QR
		$qr = null;
		if (!empty($confirmation) && !empty($confirmation->barcodes)) {
			$barcode = current($confirmation->barcodes);

			$qr = [
					'#type' => 'container',
					'#attributes' => ['class' => ['text-center', 'qr-holder']],
					'data' => [
							'#markup' => '<img data-src="' . $barcode->base64 . '" alt="QR Code" />'
					]
			];
		}

		return [
				'#type' => 'container',
				'data' => [
						'product' => $product,
						'schedule' => $schedule,
						'qr' => $qr,
				]
		];
	}

	protected function generateRow($info, $quantity, $price) {
		return [
				'class' => '',
				'data' => [
						'info' => [
								'class' => '',
								'data' => [
										'#markup' => $info
								]
						],
						'quantity' => [
								'class' => '',
								'data' => [
										'#markup' => $quantity
								]
						],
						'price' => [
								'class' => '',
								'data' => [
										'#markup' => $this->moneyFormat($price)
								]
						],
				],
		];
	}

	protected function generateProductResults($products, $arrivalDate, $departureDate, $promoCode = null) {
		$productResults = [
				'modals' => []
		];

		$productResults['results'] = [
				'#type' => 'container',
				'#theme' => 'table',
				'#responsive' => false,
				'#cache' => ['max-age' => 0],
				'#attributes' => ['class' => ['table', 'table-normal', 'table-results', 'is-desktop']],
				'#empty' => $this->t('Aucun résultat.')
		];

		$productResults['results-mobile'] = [
				'#type' => 'container',
				'#cache' => ['max-age' => 0],
				'#attributes' => ['class' => ['parking-result', 'is-mobile']]
		];

		$carParks = $this->advam->getCarParks();
		$availabilities = $this->advam->getParkingAvailability([
				'promotionCode' => $promoCode,
				'arrivalDate' => date('Y-m-d', strtotime($arrivalDate)),
				'arrivalTime' => date('H:i', strtotime($arrivalDate)),
				'departureDate' => date('Y-m-d', strtotime($departureDate)),
				'departureTime' => date('H:i', strtotime($departureDate)),
		]
		);

		if ($products) {
			foreach ($products as $id => $product) {
				$availability = clone $availabilities;
				$carPark = [];

				// Set availability
				foreach ($availability->results as $key => $result) {
					if ($result->carPark !== $product->carParkId) {
						unset($availability->results[$key]);
					}
				}

				foreach ($availability->carParks as $key => $result) {
					if ($result->id !== $product->carParkId) {
						unset($availability->carParks[$key]);
					}
				}

				foreach ($availability->products as $key => $result) {
					if ($result->carParkId !== $product->carParkId) {
						unset($availability->products[$key]);
					}
				}

				// Set car park
				foreach ($carParks as $value) {
					if ($value->id === $product->carParkId) {
						$carPark = $value;
						break;
					}
				}

				$parkingSlug = strtolower($product->code);

				switch ($parkingSlug) {
					case 'p1':
						$parkingSlug = 'inparq';
					break;
					case 'p2':
						$parkingSlug = 'proxiparq';
					break;
				}

				$row = [
						'class' => 'parking-' . $parkingSlug,
						'data' => [],
				];

				$name = [
						'class' => 'parking-name parking-name-' . $parkingSlug,
						'data' => ['#markup' => '<span class="icon icon-' . $parkingSlug . '" data-grunticon-embed></span>'],
				];

				$transfer = [
						'class' => 'parking-walking-time',
						'data' => [
								'#markup' => $this->t('<span class="icon icon-walking" data-grunticon-embed></span><span class="text">@time minutes<br>à pied</span>',
										[
												'@time' => reset($carPark->transfers)->transferTime,
										]
								)
						],
				];

				$description = [
						'class' => 'parking-info',
						'data' => [
								'#type' => 'container',
								'description' => [
										'#markup' => $product->description
								],
								'transfer' => [
										'#type' => 'container',
										'#attributes' => ['class' => ['parking-walking-time']],
										'#markup' => $transfer['data']['#markup']
								],
						],
				];

				$locationModal = $this->generateModal('location-' . $carPark->id,
						$this->t('Localisation sur la carte'),
						[
								'map' => [
										'#type' => 'html_tag',
										'#tag' => 'img',
										'#attributes' => ['src' => $carPark->mapImageUrl, 'width' => '100%']
								],
						]
				);

				$informationModal = $this->generateModal('information-' . $carPark->id,
						$this->t('Information supplémentaire'),
						[
								'info' => [
										'#type' => 'html_tag',
										'#tag' => 'div',
										'#value' => $carPark->description
								],
						]
				);

				$productResults['modals']['modal-location-' . $carPark->id] = $locationModal;
				$productResults['modals']['modal-info-' . $carPark->id] = $informationModal;

				$localisation = [
						'class' => 'parking-localisation',
						'data' => [
								'#type' => 'container',
							//                        'locationModal' => $locationModal,
							//                        'informationModal' => $informationModal,
								'location' => [
										'#type' => 'html_tag',
										'#tag' => 'a',
										'#value' => $this->t('<span class="icon icon-access" data-grunticon-embed></span><span>Localiser sur la carte</span>'),
										'#attributes' => [
												'href' => '#',
												'data-toggle' => 'modal',
												'data-target' => '#location-' . $product->carParkId,
										]
								],
								'information' => [
										'#type' => 'html_tag',
										'#tag' => 'a',
										'#value' => $this->t('<span class="icon icon-info" data-grunticon-embed></span><span>Information supplémentaire</span>'),
										'#attributes' => [
												'href' => '#',
												'data-toggle' => 'modal',
												'data-target' => '#information-' . $product->carParkId,
										]
								],
						],
				];

				$buttonLabel = (empty($availability->results)) ? $this->t('Non disponible') : $this->t('Réserver');
				$buttonLabel = ($product->isSoldOut && $product->displaySoldOut) ? $this->t('Complet') : $buttonLabel;
				$buttonState = (($product->isSoldOut && $product->displaySoldOut) || empty($availability->results)) ? 'disabled' : null;

				$link = [
						'class' => 'parking-price-button',
						'data' => [
								'#type' => 'container',
								'prices' => [
										'#type' => 'container',
										'#attributes' => ['class' => ['price-wrapper']],
										'original-price' => (reset($availability->results)->originalPrice !== reset($availability->results)->totalPrice) ? [
												'#markup' => '<span class="text text-stroke">' . $this->moneyFormat(reset($availability->results)->originalPrice) . '</span><br />',
										] : null,
										'price' => (!empty($availability->results)) ? [
												'#markup' => '<span class="text">' . $this->moneyFormat(reset($availability->results)->totalPrice) . '</span>',
										] : null,
								],
								'product_id' => [
										'#type' => 'hidden',
										'#title' => $this->t('Product ID'),
										'#value' => $product->id,
										'#name' => 'product_id',
										'#required' => TRUE,
								],
								'button' => [
										'#type' => 'submit',
										'#value' => $buttonLabel,
										'#attributes' => [
												'class' => ['form-group', 'btn', 'btn-default', 'btn-product-id', $buttonState],
												'disabled' => $buttonState,
												'data-product-id' => $product->id
										]
								]
						]
				];

				$row['data'][] = $name;
				$row['data'][] = $description;
				$row['data'][] = $transfer;
				$row['data'][] = $localisation;
				$row['data'][] = $link;

				$productResults['results']['#rows'][] = $row;

				$productResults['results-mobile'][$parkingSlug] = [
						'#type' => 'container',
						'#attributes' => ['class' => ['parking-available', 'parking-' . $parkingSlug]],

						'name' => [
								'#type' => 'container',
								'#attributes' => ['class' => ['parking-element', 'parking-name', 'parking-name-' . $parkingSlug]],
								'#markup' => $name['data']['#markup'],
						],

						'items' => [
								'#type' => 'container',
								'#attributes' => ['class' => ['parking-elements-wrapper']],

								'info' => [
										'#type' => 'container',
										'#attributes' => ['class' => ['parking-element', 'parking-info']],
										'#markup' => reset($availability->products)->description
								],

								'walking-time' => [
										'#type' => 'container',
										'#attributes' => ['class' => ['parking-element', 'parking-walking-time']],
										'#markup' => $transfer['data']['#markup'],
								],

								'localisation' => [
										'#type' => 'container',
										'#attributes' => ['class' => ['parking-element', 'parking-localisation']],

										'wrapper' => array_merge($localisation['data'],
												[
														'#type' => 'container',
														'#attributes' => ['class' => ['form-group']]
												]
										)
								],
						],

						'price' => [
								'#type' => 'container',
								'#attributes' => ['class' => ['parking-element', 'parking-price-button']],

								'wrapper' => array_merge($link['data'],
										[
												'#type' => 'container',
												'#attributes' => ['class' => ['form-group']]
										]
								)
						],
				];
			}
		}

		return $productResults;
	}

	protected function generateBookingOrderConfirmation($booking, $modification = null) {
		$rows = [
				[
						['data' => ['#markup' => '<strong>' . $this->t('Référence de réservation') . '</strong>']],
						$booking->reference
				],
				[
						['data' => ['#markup' => '<strong>' . $this->t('Entrée') . '</strong>']],
						date('Y-m-d H:i', strtotime($booking->arrivalDate . ' ' . $booking->arrivalTime))
				],
				[
						['data' => ['#markup' => '<strong>' . $this->t('Sortie') . '</strong>']],
						date('Y-m-d H:i', strtotime($booking->departureDate . ' ' . $booking->departureTime))
				]
		];

		$extras = $this->advam->getExtras();

		foreach ($booking->items as $type => $items) {
			foreach ($items as $key => $item) {
				$label = $type;

				switch ($type) {
					case 'parkings':
						$label = $this->t('Stationnement');
					break;
					case 'extras':
						$label = $this->t('Extra');
					break;
				}

				if ($type === 'extras') {
					foreach ($item->offerLines as $itemLine) {
						// Search for product name
						foreach ($extras as $extra) {
							if ($extra->id === $item->extraId) {
								foreach ($extra->offerLines as $offerLine) {
									if ($offerLine->id === $itemLine->extraOfferLineId && $offerLine->description) {
										$label = strip_tags($offerLine->description);
										break;
									}
								}
							}
						}

						$rows[] = [
								['data' => ['#markup' => '<strong>' . $itemLine->quantity . ' x ' . $label . '</strong>']],
								$this->moneyFormat($itemLine->amount)
						];
					}
				} else {
					$rows[] = [
							['data' => ['#markup' => '<strong>' . $label . '</strong>']],
							$this->moneyFormat($item->amount)
					];
				}
			}
		}

		if ($booking->validPromotionCode) {
			$rows[] = [
					['data' => ['#markup' => '<strong>' . $this->t('Code promotionnel') . '</strong>' . ' (' . $booking->promotionCode . ')']],
					'-' . $this->moneyFormat($booking->promotionSaving)
			];
		}

		// Total
		if (!$modification) {
			$rows[] = [
					['data' => ['#markup' => '<strong>' . $this->t('Total') . '</strong>']],
					$this->moneyFormat($this->store->get('booking')->price)
			];
		} else {
			$rows[] = [
					['data' => ['#markup' => '<strong>' . $this->t('Total original') . '</strong>']],
					$this->moneyFormat($modification->originalBookingValue)
			];

			$rows[] = [
					['data' => ['#markup' => '<strong>' . $this->t('Nouveau total') . '</strong>']],
					$this->moneyFormat($modification->newBookingValue)
			];
		}


		return [
				'#type' => 'table',
				'#attributes' => ['class' => ['table', 'table-normal', 'table-summary']],
				'#caption' => $this->t('Votre commande'),
				'#rows' => $rows
		];
	}

	protected function generateBookingDetailsConfirmation($booking) {
		$rows = [
				[
						['data' => ['#markup' => '<strong>' . $this->t('Prénom') . '</strong>']],
						$booking->firstName
				],
				[
						['data' => ['#markup' => '<strong>' . $this->t('Nom') . '</strong>']],
						$booking->lastName
				],
				[
						['data' => ['#markup' => '<strong>' . $this->t('Courriel') . '</strong>']],
						$booking->email
				],
				[
						['data' => ['#markup' => '<strong>' . $this->t('Téléphone') . '</strong>']],
						$booking->phone
				],
				[
						['data' => ['#markup' => '<strong>' . $this->t('Adresse') . '</strong>']],
						$booking->addressLine1
				],
				[
						['data' => ['#markup' => '<strong>' . $this->t('Ville') . '</strong>']],
						$booking->town
				],
				[
						['data' => ['#markup' => '<strong>' . $this->t('Code postal') . '</strong>']],
						$booking->postcode
				],
		];

		return [
				'#type' => 'table',
				'#attributes' => ['class' => ['table', 'table-normal', 'table-summary']],
				'#caption' => $this->t('Vos informations'),
				'#rows' => $rows
		];
	}

	/**
	 * Returns a formatted number
	 * Fallbacks on Windows
	 * @param $pattern
	 * @param $value
	 * @param string $currency
	 * @return string
	 */
	protected function moneyFormat($value, $currency = 'CAD') {
		if (extension_loaded('intl')) {
			$fmt = new NumberFormatter(\Drupal::languageManager()->getCurrentLanguage()->getId(), NumberFormatter::CURRENCY);
			return $fmt->formatCurrency($value, $currency);
		} else if (function_exists('money_format')) {
			return money_format('%i$', preg_replace('/[^0-9\.]/', '', $value));
		} else {
			return number_format($value, 2) . '$';
		}
	}

	/**
	 * Used to redirect with webview param
	 * @param FormStateInterface $form_state
	 * @param $route
	 */
	protected function parkingRedirect($form_state, $route) {
		$params = ($this->store->get('webview') || $this->getRequest()->query->get('webview')) ? ['webview' => 1] : [];

		$form_state->setRedirect($route, $params);
	}
}
