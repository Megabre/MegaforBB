<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Exception;
use Forecor\Core\Validation\Validator;

abstract class FormRequest
{
    /** @var array */
    protected $data = [];

    /** @var Validator|null */
    protected $validator = null;

    /**
     * @param array|null $data İsteğe bağlı doğrulama verisi, null ise $_POST kullanılır.
     */
    public function __construct(?array $data = null)
    {
        $this->data = $data ?? $_POST;
    }

    /**
     * Alt sınıflar validasyon kurallarını burada tanımlar.
     */
    abstract public function rules(): array;

    /**
     * Özelleştirilmiş hata mesajları.
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Güvenlik veya yetki gerektiren kontrolleri yapmak için ezilebilir.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validasyonu çalıştırır. Başarısız olursa Exception/Ajax array fırlatılması beklenebilir
     * veya hataları doğrudan dizebiliriz. Bu metot direkt Validator nesnesini sarmalar.
     *
     * @return bool
     * @throws Exception Eğer authorize false dönerse
     */
    public function validate(): bool
    {
        if (!$this->authorize()) {
            throw new Exception('Unauthorized action.');
        }

        $this->validator = validator($this->data, $this->rules(), $this->messages());

        return !$this->validator->fails();
    }

    /**
     * Doğrulanmış veriyi (sadece kuralları olan kısımları) döndürür veya ham veriyi getirir.
     */
    public function validated(): array
    {
        $rules = $this->rules();
        $validatedData = [];
        foreach (array_keys($rules) as $key) {
            if (array_key_exists($key, $this->data)) {
                $validatedData[$key] = $this->data[$key];
            }
        }
        return $validatedData;
    }

    /**
     * Hataları dizi olarak döndürür.
     */
    public function errors(): array
    {
        return $this->validator ? $this->validator->errors() : [];
    }

    /**
     * İlk hatayı string olarak döndürür.
     */
    public function firstError(): ?string
    {
        return $this->validator ? $this->validator->firstError() : null;
    }

    /**
     * Verinin içinden belirli bir key okur.
     */
    public function input(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Tüm POST/Data dizisini döndürür.
     */
    public function all(): array
    {
        return $this->data;
    }
}
