<?php

/**
 * Drago Extension
 * Package built on Nette Framework
 */

declare(strict_types=1);

namespace Drago\Commerce\UI\Order;

use Brick\PhoneNumber\PhoneNumber;
use Nette\Utils\ArrayHash;


/**
 * Data transfer object for customer information.
 * Represents customer details used in forms and order processing.
 */
class CustomerData extends ArrayHash
{
	public const string
		Id = 'id',
		Email = 'email',
		Phone = 'phone',
		Name = 'name',
		Surname = 'surname',
		Street = 'street',
		City = 'city',
		PostalCode = 'postal_code',
		Country = 'country',
		Note = 'note',
		CreatedAt = 'created_at';

	public int $id;
	public string $email;
	public PhoneNumber $phone;
	public string $name;
	public string $surname;
	public string $street;
	public string $city;
	public string $postal_code;
	public string $country;
	public string $note;
	public ?\DateTimeImmutable $created_at = null;
}
