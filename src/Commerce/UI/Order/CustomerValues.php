<?php declare(strict_types=1);

namespace Drago\Commerce\UI\Order;

use Brick\PhoneNumber\PhoneNumber;
use Nette\Utils\ArrayHash;


/** Holds customer information submitted by the checkout form. */
class CustomerValues extends ArrayHash
{
	public const string Id = 'id',
		Email = 'email',
		Phone = 'phone',
		Name = 'name',
		Surname = 'surname',
		Street = 'street',
		City = 'city',
		PostalCode = 'postalCode',
		Country = 'country',
		Note = 'note',
		CreatedAt = 'createdAt';

	/** sizes of the columns of the customers table */
	public const int EmailSize = 254,
		NameSize = 255,
		SurnameSize = 255,
		StreetSize = 255,
		CitySize = 255,
		PostalCodeSize = 20;

	public int $id;
	public string $email;
	public PhoneNumber $phone;
	public string $name;
	public string $surname;
	public string $street;
	public string $city;
	public string $postalCode;
	public string $country;
	public string $note;
	public ?\DateTimeImmutable $createdAt = null;
}
