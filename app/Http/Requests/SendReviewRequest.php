<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendReviewRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'reviews'    => ['required', 'array'],
            'reviews.*.id'  => [
                'required',
                Rule::exists('order_items', 'id')->where('order_id', $this->route('order')->id),
            ],
            'reviews.*.rating' => ['required', 'integer', 'between:1,5'],
            'reviews.*.review' => ['required'],
        ];
    }

    public function attribute()
    {
        return [
            'reviews.*.rating'   => '评分',
            'reviews.*.review'   => '评价',
        ];
    }
}
