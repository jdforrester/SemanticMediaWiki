<?php

namespace SMW\Query\Parser;

use SMW\DataValueFactory;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Description;
use SMW\Query\Language\Disjunction;
use SMW\Query\DescriptionFactory;
use SMW\DIProperty;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 * @author Markus Krötzsch
 */
class DescriptionProcessor {

	/**
	 * @var DataValueFactory
	 */
	private $dataValueFactory;

	/**
	 * @var DescriptionFactory
	 */
	private $descriptionFactory;

	/**
	 * @var integer
	 */
	private $queryFeatures;

	/**
	 * @var array
	 */
	private $errors = array();

	/**
	 * @since 2.4
	 *
	 * @param integer $queryFeatures
	 */
	public function __construct( $queryFeatures = false ) {
		$this->queryFeatures = $queryFeatures === false ? $GLOBALS['smwgQFeatures'] : $queryFeatures;
		$this->dataValueFactory = DataValueFactory::getInstance();
		$this->descriptionFactory = new DescriptionFactory();
	}

	/**
	 * @since 2.4
	 */
	public function clear() {
		$this->errors = array();
	}

	/**
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @since 2.4
	 *
	 * @param array|string $error
	 */
	public function addError( $error ) {
		$this->errors = array_merge( $this->errors, (array)$error );
	}

	/**
	 * @since 2.4
	 *
	 * @param string $msgKey
	 */
	public function addErrorWithMsgKey( $msgKey /*...*/ ) {

		$params = func_get_args();
		array_shift( $params );

		$message = new \Message( $msgKey, $params );
		$this->addError( str_replace( array( '[' ), array( '&#x005B;' ), $message->inContentLanguage()->text() ) );
	}

	/**
	 * @since 2.4
	 *
	 * @param DIProperty $property
	 * @param string $chunk
	 *
	 * @return Description|null
	 */
	public function getDescriptionForPropertyObjectValue( DIProperty $property, $chunk ) {

		$dataValue = $this->dataValueFactory->newPropertyObjectValue( $property );
		$dataValue->setQueryConditionUsage( true ); // FIXME

		$description = $dataValue->getQueryDescription( $chunk );
		$this->addError( $dataValue->getErrors() );

		return $description;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $chunk
	 *
	 * @return Description|null
	 */
	public function getDescriptionForWikiPageValueChunk( $chunk ) {

		// Only create a simple WpgValue to initiate the query description target
		// operation. If the chunk contains something like "≤Issue/1220" then the
		// WpgValue would return with an error as it cannot parse ≤ as/ legal
		// character, the chunk itself is processed by
		// DataValue::getQueryDescription hence no need to use it as input for
		// the factory instance
		$dataValue = $this->dataValueFactory->newTypeIDValue( '_wpg', 'QP_WPG_TITLE' );
		$description = null;

		$description = $dataValue->getQueryDescription( $chunk );
		$this->addError( $dataValue->getErrors() );

		return $description;
	}

	/**
	 * @since 2.4
	 *
	 * @param Description|null $currentDescription
	 * @param Description|null $newDescription
	 *
	 * @return Description|null
	 */
	public function getDisjunctiveCompoundDescriptionFrom( Description $currentDescription = null, Description $newDescription = null ) {
		return $this->getCompoundDescription( $currentDescription, $newDescription, SMW_DISJUNCTION_QUERY );
	}

	/**
	 * @since 2.4
	 *
	 * @param Description|null $currentDescription
	 * @param Description|null $newDescription
	 *
	 * @return Description|null
	 */
	public function getConjunctiveCompoundDescriptionFrom( Description $currentDescription = null, Description $newDescription = null ) {
		return $this->getCompoundDescription( $currentDescription, $newDescription, SMW_CONJUNCTION_QUERY );
	}

	/**
	 * Extend a given description by a new one, either by adding the new description
	 * (if the old one is a container description) or by creating a new container.
	 * The parameter $conjunction determines whether the combination of both descriptions
	 * should be a disjunction or conjunction.
	 *
	 * In the special case that the current description is NULL, the new one will just
	 * replace the current one.
	 *
	 * The return value is the expected combined description. The object $currentDescription will
	 * also be changed (if it was non-NULL).
	 */
	private function getCompoundDescription( Description $currentDescription = null, Description $newDescription = null, $compoundType = SMW_CONJUNCTION_QUERY ) {

		$notallowedmessage = 'smw_noqueryfeature';

		if ( $newDescription instanceof SomeProperty ) {
			$allowed = $this->queryFeatures & SMW_PROPERTY_QUERY;
		} elseif ( $newDescription instanceof ClassDescription ) {
			$allowed = $this->queryFeatures & SMW_CATEGORY_QUERY;
		} elseif ( $newDescription instanceof ConceptDescription ) {
			$allowed = $this->queryFeatures & SMW_CONCEPT_QUERY;
		} elseif ( $newDescription instanceof Conjunction ) {
			$allowed = $this->queryFeatures & SMW_CONJUNCTION_QUERY;
			$notallowedmessage = 'smw_noconjunctions';
		} elseif ( $newDescription instanceof Disjunction ) {
			$allowed = $this->queryFeatures & SMW_DISJUNCTION_QUERY;
			$notallowedmessage = 'smw_nodisjunctions';
		} else {
			$allowed = true;
		}

		if ( !$allowed ) {
			$this->addErrorWithMsgKey( $notallowedmessage, $newDescription->getQueryString() );
			return $currentDescription;
		}

		if ( $newDescription === null ) {
			return $currentDescription;
		} elseif ( $currentDescription === null ) {
			return $newDescription;
		} else { // we already found descriptions
			return $this->newCompoundDescriptionFor( $compoundType, $currentDescription, $newDescription );
		}
	}

	private function newCompoundDescriptionFor( $compoundType, $currentDescription, $newDescription ) {

		if ( ( ( $compoundType & SMW_CONJUNCTION_QUERY ) != 0 && ( $currentDescription instanceof Conjunction ) ) ||
		     ( ( $compoundType & SMW_DISJUNCTION_QUERY ) != 0 && ( $currentDescription instanceof Disjunction ) ) ) { // use existing container
			$currentDescription->addDescription( $newDescription );
			return $currentDescription;
		} elseif ( ( $compoundType & SMW_CONJUNCTION_QUERY ) != 0 ) { // make new conjunction
			return $this->newConjunctionFor( $currentDescription, $newDescription );
		} elseif ( ( $compoundType & SMW_DISJUNCTION_QUERY ) != 0 ) { // make new disjunction
			return $this->newDisjunctionFor( $currentDescription, $newDescription );
		}
	}

	private function newConjunctionFor( $currentDescription, $newDescription ) {

		if ( $this->queryFeatures & SMW_CONJUNCTION_QUERY ) {
			return $this->descriptionFactory->newConjunction( array( $currentDescription, $newDescription ) );
		}

		$this->addErrorWithMsgKey( 'smw_noconjunctions', $newDescription->getQueryString() );

		return $currentDescription;
	}

	private function newDisjunctionFor( $currentDescription, $newDescription ) {

		if ( $this->queryFeatures & SMW_DISJUNCTION_QUERY ) {
			return $this->descriptionFactory->newDisjunction( array( $currentDescription, $newDescription ) );
		}

		$this->addErrorWithMsgKey( 'smw_nodisjunctions', $newDescription->getQueryString() );

		return $currentDescription;
	}

}
