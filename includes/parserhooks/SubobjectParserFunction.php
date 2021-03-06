<?php

namespace SMW;

use Parser;

/**
 * Provides the {{#subobject}} parser function
 *
 * @see http://www.semantic-mediawiki.org/wiki/Help:ParserFunction
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SubobjectParserFunction {

	/**
	 * Fixed identifier that describes the sortkey annotation parameter
	 */
	const PARAM_SORTKEY = '@sortkey';

	/**
	 * Fixed identifier that describes the subobject category parameter.
	 *
	 * We keep it as a @ fixed parameter since the standard annotation would
	 * require special attention (Category:;instead of ::) when annotating a
	 * category
	 */
	const PARAM_CATEGORY = '@category';

	/**
	 * @var ParserData
	 */
	protected $parserData;

	/**
	 * @var Subobject
	 */
	protected $subobject;

	/**
	 * @var MessageFormatter
	 */
	protected $messageFormatter;

	/**
	 * @var DataValueFactory
	 */
	private $dataValueFactory = null;

	/**
	 * @var boolean
	 */
	private $useFirstElementForPropertyLabel = false;

	/**
	 * @since 1.9
	 *
	 * @param ParserData $parserData
	 * @param Subobject $subobject
	 * @param MessageFormatter $messageFormatter
	 */
	public function __construct( ParserData $parserData, Subobject $subobject, MessageFormatter $messageFormatter ) {
		$this->parserData = $parserData;
		$this->subobject = $subobject;
		$this->messageFormatter = $messageFormatter;
		$this->dataValueFactory = DataValueFactory::getInstance();
	}

	/**
	 * @since 1.9
	 *
	 * @param boolean $useFirstElementForPropertyLabel
	 *
	 * @return SubobjectParserFunction
	 */
	public function setFirstElementForPropertyLabel( $useFirstElementForPropertyLabel = true ) {
		$this->useFirstElementForPropertyLabel = (bool)$useFirstElementForPropertyLabel;
		return $this;
	}

	/**
	 * @since 1.9
	 *
	 * @param ParserParameterProcessor $params
	 *
	 * @return string|null
	 */
	public function parse( ParserParameterProcessor $parameters ) {

		if ( $this->addDataValuesToSubobject( $parameters ) && !$this->subobject->getSemanticData()->isEmpty() ) {
			$this->parserData->getSemanticData()->addSubobject( $this->subobject );
		}

		$this->parserData->pushSemanticDataToParserOutput();

		return $this->messageFormatter
			->addFromArray( $this->subobject->getErrors() )
			->addFromArray( $this->parserData->getErrors() )
			->addFromArray( $parameters->getErrors() )
			->getHtml();
	}

	protected function addDataValuesToSubobject( ParserParameterProcessor $parameters ) {

		$subject = $this->parserData->getSemanticData()->getSubject();

		// Named subobjects containing a "." in the first five characters are reserved to be
		// used by extensions only in order to separate them from user land and avoid them
		// accidentally to refer to the same named ID
		// (i.e. different access restrictions etc.)
		if ( strpos( mb_substr( $parameters->getFirst(), 0, 5 ), '.' ) !== false ) {
			return $this->addErrorWithMsg(
				$subject,
				wfMessage( 'smw-subobject-parser-invalid-naming-scheme', $parameters->getFirst() )->escaped()
			);
		}

		$this->subobject->setEmptyContainerForId(
			$this->createSubobjectId( $parameters )
		);

		foreach ( $this->transformParametersToArray( $parameters ) as $property => $values ) {

			if ( $property === self::PARAM_SORTKEY ) {
				$property = DIProperty::TYPE_SORTKEY;
			}

			if ( $property === self::PARAM_CATEGORY ) {
				$property = DIProperty::TYPE_CATEGORY;
			}

			foreach ( $values as $value ) {

				$dataValue = $this->dataValueFactory->newPropertyValue(
						$property,
						$value,
						false,
						$subject
					);

				$this->subobject->addDataValue( $dataValue );
			}
		}

		return true;
	}

	private function createSubobjectId( ParserParameterProcessor $parameters ) {

		$isAnonymous = in_array( $parameters->getFirst(), array( null, '' ,'-' ) );

		$this->useFirstElementForPropertyLabel = $this->useFirstElementForPropertyLabel && !$isAnonymous;

		if ( $this->useFirstElementForPropertyLabel || $isAnonymous ) {
			return HashBuilder::createHashIdForContent( $parameters->toArray(), '_' );
		}

		return $parameters->getFirst();
	}

	private function transformParametersToArray( ParserParameterProcessor $parameters ) {

		if ( $this->useFirstElementForPropertyLabel ) {
			$parameters->addParameter(
				$parameters->getFirst(),
				$this->parserData->getTitle()->getPrefixedText()
			);
		}

		return $parameters->toArray();
	}

	private function addErrorWithMsg( $subject, $errorMsg ) {

		$error = new Error( $subject );

		$this->parserData->getSemanticData()->addPropertyObjectValue(
			$error->getProperty(),
			$error->getContainerFor(
				new DIProperty( '_SOBJ' ),
				$errorMsg
			)
		);

		$this->parserData->addError( $errorMsg );

		return false;
	}

}
