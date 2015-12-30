<?php

namespace SMW\Query;

use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Description;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ValueDescription;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\ConceptDescription;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMWDataItem as DataItem;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class DescriptionFactory {

	/**
	 * @since 2.4
	 *
	 * @param DataItem $dataItem
	 * @param DIProperty|null $property = null
	 * @param integer $comparator
	 *
	 * @return ValueDescription
	 */
	public function newValueDescription( DataItem $dataItem, DIProperty $property = null, $comparator = SMW_CMP_EQ ) {
		return new ValueDescription( $dataItem, $property, $comparator );
	}

	/**
	 * @since 2.4
	 *
	 * @param DIProperty $property
	 * @param Description $description
	 *
	 * @return SomeProperty
	 */
	public function newSomeProperty( DIProperty $property, Description $description ) {
		return new SomeProperty( $property, $description );
	}

	/**
	 * @since 2.4
	 *
	 * @return ThingDescription
	 */
	public function newThingDescription() {
		return new ThingDescription();
	}

	/**
	 * @since 2.4
	 *
	 * @param Description[] $descriptions
	 *
	 * @return Disjunction
	 */
	public function newDisjunction( $descriptions = array() ) {
		return new Disjunction( $descriptions );
	}

	/**
	 * @since 2.4
	 *
	 * @param Description[] $descriptions
	 *
	 * @return Conjunction
	 */
	public function newConjunction( $descriptions = array() ) {
		return new Conjunction( $descriptions );
	}

	/**
	 * @since 2.4
	 *
	 * @param integer $ns
	 *
	 * @return NamespaceDescription
	 */
	public function newNamespaceDescription( $ns ) {
		return new NamespaceDescription( $ns );
	}

	/**
	 * @since 2.4
	 *
	 * @param DIWikiPage $category
	 *
	 * @return ClassDescription
	 */
	public function newClassDescription( DIWikiPage $category ) {
		return new ClassDescription( $category );
	}

	/**
	 * @since 2.4
	 *
	 * @param DIWikiPage $concept
	 *
	 * @return ConceptDescription
	 */
	public function newConceptDescription( DIWikiPage $concept ) {
		return new ConceptDescription( $concept );
	}

}