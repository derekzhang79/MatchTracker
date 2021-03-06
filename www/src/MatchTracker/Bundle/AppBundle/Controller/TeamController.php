<?php

namespace MatchTracker\Bundle\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

use MatchTracker\Bundle\AppBundle\Form\PlayersType;

class TeamController extends Controller {

	/**
	 * Team overview page
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function indexAction() {
		return $this->render('MatchTrackerAppBundle:Team:index.html.twig');
	}

	/**
	 * Team edit page
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function editAction($nameCanonical, $code, Request $request) {

		/**
		 * Team object
		 * @var $team \MatchTracker\Bundle\AppBundle\Entity\Teams
		 */
		$team = $this->getDoctrine()
			->getRepository('MatchTrackerAppBundle:Teams')
			->findOneBy(array('nameCanonical' => $nameCanonical));

		// Security check
		if($team == null || ($code !== $team->getCode())) {
			return $this->redirect($this->generateUrl('homepage'));
		}

		// Get league
		$leagues = $team->getLeagues();
		$league = $leagues[0];

		// Create form with team data
		$builder = $this->createFormBuilder($team);
		$builder
			->add('name', 'text', array(
				'label' => 'Naam',
				'label_attr' => array('class' => 'control-label'),
				'attr' => array('placeholder' => 'Naam')
			));

		// If league has no place or fields, then ask the team for a place & time
		if($league->getPlace() == null && $league->getFields() == null) {
			$builder
				->add('place', 'text', array(
					'label' => 'Adres',
					'label_attr' => array('class' => 'control-label'),
					'attr' => array('placeholder' => 'Adres')
				))
				->add('weekday', 'choice', array(
					'choices' => array(
						'mon'    => 'Maandag',
						'tue'    => 'Dinsdag',
						'wed'    => 'Woensdag',
						'thu'    => 'Donderdag',
						'fri'    => 'Vrijdag',
						'sat'    => 'Zaterdag',
						'sun'    => 'Zondag',
					),
					'multiple' => true,
					'expanded' => true,
					'label' => 'Dag van de week',
					'label_attr' => array('class' => 'control-label'),
					'attr' => array('placeholder' => 'Dag van den week', 'size' => '7')
				))
				->add('hours', 'text', array(
					'label' => 'Uren',
					'label_attr' => array('class' => 'control-label'),
					'attr' => array('placeholder' => 'Uren')
				));
		}

		// Add the players to the form
		$form = $builder
			->add('players', 'collection', array(
			'label' => 'Spelers',
			'label_attr' => array('class' => 'control-label'),
			'type' => new PlayersType(),
			'allow_add' => true,
			'allow_delete' => true,
			'by_reference' => false
		))->getForm();

		// Form is submitted
	if ($request->isMethod('POST')) {
		$form->bind($request);

		// Form is valid? Start your engines!
		if ($form->isValid()) {
			// Generate a new canonical name...
			$team->generateNameCanonical();

			// Save changes to db
			$em = $this->getDoctrine()->getManager();
			$em->persist($team);
			$em->flush();

			// Redirect to new canonical url
			return $this->redirect($this->generateUrl('team_edit', array('nameCanonical' => $team->getNameCanonical(), 'code' => $team->getCode())));
		}

		}

		// Render view
		return $this->render('MatchTrackerAppBundle:Team:edit.html.twig',
			array(
				'team' => $team,
				'form' => $form->createView()
			));
	}

}