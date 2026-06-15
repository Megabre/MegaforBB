<?php

declare(strict_types=1);

namespace App\Http\Requests\Topic;

use App\Http\Requests\FormRequest;

class StoreReplyRequest extends FormRequest
{
    private int $maxPostLen;

    public function __construct(int $maxPostLen = 0, ?array $data = null)
    {
        parent::__construct($data);
        $this->maxPostLen = $maxPostLen;
    }

    public function rules(): array
    {
        $rules = [
            'body' => 'required'
        ];

        if ($this->maxPostLen > 0) {
            $rules['body'] .= '|max:' . $this->maxPostLen;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'body.required' => lang('topic.body_required'),
            'body.max'      => lang('topic.body_max', ['max' => $this->maxPostLen]),
        ];
    }

    /**
     * Boş HTML etiketlerini filtreleyerek body alanının gerçekten dolu olup olmadığını kontrol eder.
     * Video/embed (iframe, mfbb-media-embed) tek başına da geçerli içerik sayılır.
     */
    public function validate(): bool
    {
        $body = trim((string)$this->input('body', ''));
        $cleanBody = trim(strip_tags(str_replace(['&nbsp;', '&#160;', '&zwj;'], '', $body)));

        $hasEmbedOnly = ($cleanBody === '' && (
            str_contains($body, '<iframe') ||
            str_contains($body, 'mfbb-media-embed') ||
            str_contains($body, 'class="mfbb-media-embed"')
        ));

        if ($cleanBody === '' && !$hasEmbedOnly) {
            $this->data['body'] = ''; // Zorunlu alan kuralına takılması için boşaltıyoruz
        }

        return parent::validate();
    }
}
